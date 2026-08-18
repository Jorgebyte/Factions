<?php

declare(strict_types=1);

/**
 * This file is part of the Factions plugin for StreesCraft.
 *
 * (c) 2026 Jorgebyte
 *
 * Website:   https://jorgebyte.com
 * Community: https://discord.jorgebyte.com
 * Instagram: @jorgebyte_
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jorgebyte\Factions\managers\faction;

use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use Generator;
use InvalidArgumentException;
use Jorgebyte\Factions\cache\CacheMetrics;
use Jorgebyte\Factions\cache\CachePolicyService;
use Jorgebyte\Factions\cache\CachePriority;
use Jorgebyte\Factions\cache\FactionMemoryCache;
use Jorgebyte\Factions\entities\Claim;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\entities\Subclaim;
use Jorgebyte\Factions\event\faction\FactionPowerChangeEvent;
use Jorgebyte\Factions\integration\display\FactionDisplaySyncService;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\utils\FactionConfig;
use Jorgebyte\Factions\utils\PlayerUtils;
use Jorgebyte\Factions\utils\PositionSerializer;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class FactionManager
{
    private const TOP_CACHE_TTL = 60;
    private const ALLOWED_TOP_SORTS = ['power', 'kills', 'money'];

    /** @var FactionMemoryCache */
    private FactionMemoryCache $factionCache;

    /** @var array<string, int> */
    private array $playerFactions = [];

    private ?EconomyProvider $economyProvider = null;

    /** @var array<string, mixed> */
    private array $topCache = [];

    /** @var array<string, int> */
    private array $topCacheExpiry = [];

    /** @var array<int, Faction> */
    private array $pendingWrites = [];

    /** @var array<int, list<\Closure>> */
    private array $inFlightFactionLoads = [];

    /** @var array<string, list<\Closure>> */
    private array $inFlightPlayerFactionLoads = [];

    private ?FactionDisplaySyncService $displaySyncService = null;

    private ?ClaimManager $claimManager = null;

    private MembershipService $membershipService;

    private LeadershipService $leadershipService;

    private FactionBankService $bankService;

    public function __construct(
        private readonly DataConnector $connector,
        private readonly FactionConfig $factionConfig,
        CachePolicyService $cachePolicy,
    ) {
        $this->factionCache = new FactionMemoryCache($cachePolicy);
        $this->membershipService = new MembershipService($this, $this->connector);
        $this->leadershipService = new LeadershipService($this, $this->connector);
        $this->bankService = new FactionBankService($this);
    }

    public function setDisplaySyncService(?FactionDisplaySyncService $displaySyncService): void
    {
        $this->displaySyncService = $displaySyncService;
    }

    public function setClaimManager(ClaimManager $claimManager): void
    {
        $this->claimManager = $claimManager;
    }

    public function syncFactionDisplay(Faction $faction): void
    {
        $this->displaySyncService?->syncFactionPlayers($faction);
    }

    public function syncPlayerDisplayByXuid(string $xuid): void
    {
        $player = PlayerUtils::getPlayerByXuid($xuid);
        if ($player !== null) {
            $this->displaySyncService?->syncPlayerState($player);
        }
    }

    public function clearPlayerDisplayByXuid(string $xuid): void
    {
        $this->displaySyncService?->clearPlayerStateByXuid($xuid);
    }

    public function clearFactionDisplay(Faction $faction): void
    {
        $this->displaySyncService?->clearFactionPlayers($faction);
    }

    public function setPlayerFactionMapping(string $xuid, int $factionId): void
    {
        $this->playerFactions[$xuid] = $factionId;
    }

    public function clearPlayerFactionMapping(string $xuid): void
    {
        unset($this->playerFactions[$xuid]);
    }

    public function removeFactionFromCache(int $factionId): void
    {
        $this->factionCache->remove($factionId);
    }

    public function purgeFactionClaims(int $factionId): void
    {
        $this->claimManager?->purgeFactionClaims($factionId);
    }

    /**
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, Faction|null>
     */
    public function loadFaction(int $factionId): Generator
    {
        $faction = $this->factionCache->get($factionId);
        if ($faction !== null) {
            return $faction;
        }

        if (isset($this->inFlightFactionLoads[$factionId])) {
            return yield from $this->awaitInFlightFactionLoad($factionId);
        }

        $this->inFlightFactionLoads[$factionId] = [];

        $resolvedFaction = null;
        try {
            [$rows, $members, $alliances, $claims, $perms, $subclaims] = yield from Await::all([
                $this->connector->asyncSelect("factions.get_by_id", ["id" => $factionId]),
                $this->connector->asyncSelect("members.get_by_faction", ["faction_id" => $factionId]),
                $this->connector->asyncSelect("alliances.get_by_faction", ["faction_id" => $factionId]),
                $this->connector->asyncSelect("claims.get_by_faction", ["faction_id" => $factionId]),
                $this->connector->asyncSelect("faction_permissions.get_by_faction", ["faction_id" => $factionId]),
                $this->connector->asyncSelect("subclaims.get_by_faction", ["faction_id" => $factionId]),
            ]);
            if (!empty($rows)) {
                $factionData = $rows[0];
                $resolvedFaction = new Faction(
                    (int) $factionData['id'],
                    (string) $factionData['name'],
                    (int) $factionData['creation_date'],
                    (string) $factionData['leader_xuid'],
                    PositionSerializer::deserialize($factionData['home']),
                    (int) $factionData['power'],
                    (float) $factionData['money'],
                    (int) $factionData['kills'],
                    (int) ($factionData['freeze_power_time'] ?? 0),
                );

                $resolvedFaction->permissions->loadFromDb($perms);

                foreach ($members as $member) {
                    $resolvedFaction->addMember(
                        new Member(
                            $factionId,
                            (string) $member['player_xuid'],
                            (string) $member['player_name'],
                            Role::tryFrom($member['role']) ?? Role::MEMBER
                        )
                    );
                }

                foreach ($alliances as $alliance) {
                    if (($alliance['status'] ?? null) === 'accepted') {
                        $resolvedFaction->addAlly((int) $alliance['ally_id']);
                    }
                }

                foreach ($claims as $claimData) {
                    $resolvedFaction->addClaim(new Claim(
                        (int) $claimData['faction_id'],
                        (int) $claimData['chunk_x'],
                        (int) $claimData['chunk_z'],
                        (string) $claimData['world_name']
                    ));
                }

                foreach ($subclaims as $subData) {
                    $resolvedFaction->addSubclaim(new Subclaim(
                        (int) $subData['id'],
                        (int) $subData['faction_id'],
                        (string) $subData['name'],
                        (string) $subData['world_name'],
                        (int) $subData['min_x'],
                        (int) $subData['min_y'],
                        (int) $subData['min_z'],
                        (int) $subData['max_x'],
                        (int) $subData['max_y'],
                        (int) $subData['max_z'],
                        Role::tryFrom((string) $subData['min_role']) ?? Role::COLEADER
                    ));
                }

                $this->factionCache->set($resolvedFaction);
            }
        } catch (Throwable $e) {
            $this->resolveInFlightFactionLoad($factionId, null);
            throw $e;
        }

        $this->resolveInFlightFactionLoad($factionId, $resolvedFaction);
        return $resolvedFaction;
    }

    /**
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, Faction|null>
     */
    public function loadFactionByPlayerXuid(string $playerXuid): Generator
    {
        if (isset($this->playerFactions[$playerXuid])) {
            $faction = $this->factionCache->get($this->playerFactions[$playerXuid]);
            if ($faction !== null) {
                return $faction;
            }
        }

        if (isset($this->inFlightPlayerFactionLoads[$playerXuid])) {
            return yield from $this->awaitInFlightPlayerFactionLoad($playerXuid);
        }

        $this->inFlightPlayerFactionLoads[$playerXuid] = [];

        try {
            $rows = yield from $this->connector->asyncSelect("members.get_faction_id_by_xuid", [
                "player_xuid" => $playerXuid,
            ]);

            if (empty($rows)) {
                $this->resolveInFlightPlayerFactionLoad($playerXuid, null);
                return null;
            }

            $factionId = (int) $rows[0]["faction_id"];
            $this->playerFactions[$playerXuid] = $factionId;

            $faction = yield from $this->loadFaction($factionId);
            $this->resolveInFlightPlayerFactionLoad($playerXuid, $faction);
            return $faction;
        } catch (Throwable $e) {
            $this->resolveInFlightPlayerFactionLoad($playerXuid, null);
            throw $e;
        }
    }

    public function getPlayerFaction(string $playerXuid): ?Faction
    {
        if (!isset($this->playerFactions[$playerXuid])) {
            return null;
        }

        return $this->factionCache->get($this->playerFactions[$playerXuid]);
    }

    public function getLoadedFactionById(int $factionId): ?Faction
    {
        return $this->factionCache->get($factionId);
    }

    public function getPlayerFactionId(string $playerXuid): ?int
    {
        return $this->playerFactions[$playerXuid] ?? null;
    }

    /**
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, FactionResponse>
     */
    public function createFaction(CreateFactionRequest $request): Generator
    {
        $name = trim($request->factionName);
        if ($name == '') {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $xuid = $request->leaderXuid;
        $leaderName = $request->leaderName;

        $existing = yield from $this->connector->asyncSelect("factions.get_by_name", ["name" => $name]);
        if ($existing !== []) {
            return FactionResponse::fail(FactionResult::ALREADY_EXISTS, ['faction' => $name]);
        }

        try {
            yield from $this->connector->asyncInsert("factions.insert", [
                "name" => $name,
                "creation_date" => time(),
                "leader_xuid" => $xuid,
                "home" => null,
                "power" => $this->factionConfig->getInitialPower(),
                "money" => 0.0,
                "kills" => 0,
                "freeze_power_time" => 0,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $this->invalidateTopCache();

        $rows = yield from $this->connector->asyncSelect("factions.get_by_name", ["name" => $name]);
        if (empty($rows)) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $factionId = (int) $rows[0]['id'];
        try {
            yield from $this->connector->asyncChange("members.insert", [
                "faction_id" => $factionId,
                "player_xuid" => $xuid,
                "player_name" => $leaderName,
                "role" => Role::LEADER->value,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $faction = yield from $this->loadFaction($factionId);
        if ($faction === null) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $this->playerFactions[$xuid] = $factionId;
        return FactionResponse::success([
            'faction_id' => $factionId,
            'faction' => $name,
        ]);
    }

    public function saveFaction(Faction $faction): void
    {
        $this->connector->executeChange("factions.update_all", [
            "id" => $faction->id,
            "name" => $faction->name,
            "creation_date" => $faction->creationDate,
            "leader_xuid" => $faction->leaderXuid,
            "home" => PositionSerializer::serialize($faction->home),
            "power" => $faction->power,
            "money" => $faction->money,
            "kills" => $faction->getKills(),
            "freeze_power_time" => $faction->freezePowerTime,
        ]);
        $this->invalidateTopCache();
    }

    public function queueFactionSave(Faction $faction): void
    {
        $this->pendingWrites[$faction->id] = $faction;
        $this->invalidateTopCache();
    }

    /**
     * @return Generator<mixed, mixed, mixed, void>
     */
    public function flushPendingWrites(): Generator
    {
        $writes = $this->pendingWrites;
        $this->pendingWrites = [];

        if (empty($writes)) {
            return;
        }

        $promises = [];
        foreach ($writes as $faction) {
            $promises[] = $this->connector->asyncChange("factions.update_all", [
                "id" => $faction->id,
                "name" => $faction->name,
                "creation_date" => $faction->creationDate,
                "leader_xuid" => $faction->leaderXuid,
                "home" => PositionSerializer::serialize($faction->home),
                "power" => $faction->power,
                "money" => $faction->money,
                "kills" => $faction->getKills(),
                "freeze_power_time" => $faction->freezePowerTime,
            ]);
        }
        yield from Await::all($promises);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function addMemberToFactionResponse(AddMemberRequest $request): Generator
    {
        return yield from $this->membershipService->addMemberToFaction($request);
    }

    public function updateFactionPower(Faction $faction, int $newPower): void
    {
        $oldPower = $faction->power;
        $event = new FactionPowerChangeEvent($faction, $oldPower, $newPower);
        $event->call();

        $finalPower = $event->getNewPower();
        $faction->power = $finalPower;

        $this->queueFactionSave($faction);
        $this->syncFactionDisplay($faction);
    }

    /**
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, Faction|null>
     */
    public function loadFactionByName(string $name): Generator
    {
        $rows = yield from $this->connector->asyncSelect("factions.get_by_name", [
            "name" => $name,
        ]);

        if (empty($rows)) {
            return null;
        }

        $factionId = (int) $rows[0]["id"];
        return yield from $this->loadFaction($factionId);
    }

    /**
     * @return Generator<mixed, mixed, mixed, void>
     */
    public function updatePlayerName(string $xuid, string $newName): Generator
    {
        yield from $this->connector->asyncChange("members.update_name", [
            "player_xuid" => $xuid,
            "player_name" => $newName,
        ]);

        $factionId = $this->playerFactions[$xuid] ?? null;
        if ($factionId === null) {
            return;
        }

        $faction = $this->factionCache->get($factionId);
        if ($faction === null) {
            return;
        }

        $member = $faction->getMember($xuid);
        if ($member instanceof Member) {
            $member->setPlayerName($newName);
        }
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function disbandFactionResponse(Faction $faction): Generator
    {
        return yield from $this->membershipService->disbandFaction($faction);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function leaveFactionResponse(Member $member): Generator
    {
        return yield from $this->membershipService->leaveFaction($member);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function kickMemberResponse(KickMemberRequest $request): Generator
    {
        return yield from $this->membershipService->kickMember($request);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function transferLeadershipResponse(Member $fromLeader, Member $toNewLeader): Generator
    {
        return yield from $this->leadershipService->transferLeadership($fromLeader, $toNewLeader);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function promotePlayerResponse(Member $member): Generator
    {
        return yield from $this->leadershipService->promotePlayer($member);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function demotePlayerResponse(Member $member): Generator
    {
        return yield from $this->leadershipService->demotePlayer($member);
    }

    public function updateFactionMoney(Faction $faction): void
    {
        $this->queueFactionSave($faction);
        $this->syncFactionDisplay($faction);
    }

    public function setFactionCachePriority(int $factionId, CachePriority $priority): void
    {
        $entry = $this->factionCache->getEntry($factionId);
        if ($entry !== null) {
            $entry->priority = $priority;
        }
    }

    /**
     * @return Generator<mixed, mixed, mixed, FactionResponse>
     */
    public function depositToBank(Faction $faction, float $amount): Generator
    {
        return yield from $this->bankService->deposit($faction, $amount);
    }

    /**
     * @return Generator<mixed, mixed, mixed, FactionResponse>
     */
    public function withdrawFromBank(Faction $faction, float $amount): Generator
    {
        return yield from $this->bankService->withdraw($faction, $amount);
    }

    /**
     * @return Faction[]
     */
    public function getLoadedFactions(): array
    {
        return $this->factionCache->getAllLoadedFactions();
    }

    public function getFactionCache(): FactionMemoryCache
    {
        return $this->factionCache;
    }

    public function getFactionCacheMetrics(): CacheMetrics
    {
        return $this->factionCache->getMetrics();
    }

    public function getInFlightFactionLoadCount(): int
    {
        return count($this->inFlightFactionLoads);
    }

    public function getInFlightPlayerFactionLoadCount(): int
    {
        return count($this->inFlightPlayerFactionLoads);
    }

    public function getPendingWriteCount(): int
    {
        return count($this->pendingWrites);
    }

    public function setEconomyProvider(EconomyProvider $provider): void
    {
        $this->economyProvider = $provider;
    }

    public function getEconomyProvider(): ?EconomyProvider
    {
        return $this->economyProvider;
    }

    public function cleanFactionCache(): void
    {
        $this->factionCache->clean();
    }

    /**
     * @return Generator<mixed, mixed, mixed, array<int, array<string, mixed>>> Returns an array of faction data (not full Faction objects)
     */
    public function getTopFactions(string $sortBy, int $page = 1): Generator
    {
        if (!in_array($sortBy, self::ALLOWED_TOP_SORTS, true)) {
            throw new InvalidArgumentException("Invalid sort criteria: " . $sortBy);
        }
        if ($page < 1) {
            $page = 1;
        }

        $cacheKey = "top_" . $sortBy . "_page_" . $page;

        if (isset($this->topCache[$cacheKey]) && time() < $this->topCacheExpiry[$cacheKey]) {
            /** @var array<int, array<string, mixed>> $cached */
            $cached = $this->topCache[$cacheKey];
            return $cached;
        }

        $limit = 10;
        $offset = ($page - 1) * $limit;

        $queryName = "factions.get_top_by_" . $sortBy;
        $rows = yield from $this->connector->asyncSelect($queryName, ["limit" => $limit, "offset" => $offset]);

        $this->topCache[$cacheKey] = $rows;
        $this->topCacheExpiry[$cacheKey] = time() + self::TOP_CACHE_TTL;

        return $rows;
    }

    /**
     * @return Generator<mixed, mixed, mixed, int>
     */
    public function getTotalTopPages(string $sortBy, int $perPage = 10): Generator
    {
        if (!in_array($sortBy, self::ALLOWED_TOP_SORTS, true)) {
            throw new InvalidArgumentException("Invalid sort criteria: " . $sortBy);
        }

        $cacheKey = "total_pages_" . $sortBy;

        if (isset($this->topCache[$cacheKey]) && time() < $this->topCacheExpiry[$cacheKey]) {
            return (int) $this->topCache[$cacheKey];
        }

        $rows = yield from $this->connector->asyncSelect("factions.count_all", []);
        $totalFactions = $rows[0]['total_factions'] ?? 0;
        $totalPages = (int) ceil($totalFactions / $perPage);

        $this->topCache[$cacheKey] = $totalPages;
        $this->topCacheExpiry[$cacheKey] = time() + self::TOP_CACHE_TTL;
        return $totalPages;
    }

    public function invalidateTopCache(): void
    {
        $this->topCache = [];
        $this->topCacheExpiry = [];
    }

    public function getFactionConfig(): FactionConfig
    {
        return $this->factionConfig;
    }

    /** @return Generator<mixed, mixed, mixed, void> */
    public function setPlayerDisbandCooldown(string $playerXuid): Generator
    {
        yield from $this->connector->asyncChange("player_cooldowns.set", [
            "player_xuid" => $playerXuid,
            "last_disband_time" => time(),
        ]);
    }

    /** @return Generator<mixed, mixed, mixed, int> */
    public function getPlayerDisbandCooldownRemaining(string $playerXuid): Generator
    {
        $cooldownLimit = $this->factionConfig->getDisbandCooldownSeconds();
        if ($cooldownLimit <= 0) {
            return 0;
        }

        $rows = yield from $this->connector->asyncSelect("player_cooldowns.get", [
            "player_xuid" => $playerXuid,
        ]);

        if (empty($rows)) {
            return 0;
        }

        $lastDisband = (int) ($rows[0]['last_disband_time'] ?? 0);
        $elapsed = time() - $lastDisband;
        return max(0, $cooldownLimit - $elapsed);
    }

    /** @return Generator<mixed, mixed, mixed, Faction|null> */
    private function awaitInFlightFactionLoad(int $factionId): Generator
    {
        return yield from Await::promise(function (\Closure $resolve) use ($factionId): void {
            if (!isset($this->inFlightFactionLoads[$factionId])) {
                $resolve($this->factionCache->get($factionId));
                return;
            }

            $this->inFlightFactionLoads[$factionId][] = $resolve;
        });
    }

    private function resolveInFlightFactionLoad(int $factionId, ?Faction $faction): void
    {
        $waiters = $this->inFlightFactionLoads[$factionId] ?? [];
        unset($this->inFlightFactionLoads[$factionId]);

        foreach ($waiters as $resolve) {
            try {
                $resolve($faction);
            } catch (Throwable) {
            }
        }
    }

    /** @return Generator<mixed, mixed, mixed, Faction|null> */
    private function awaitInFlightPlayerFactionLoad(string $playerXuid): Generator
    {
        return yield from Await::promise(function (\Closure $resolve) use ($playerXuid): void {
            if (!isset($this->inFlightPlayerFactionLoads[$playerXuid])) {
                $factionId = $this->playerFactions[$playerXuid] ?? null;
                $resolve($factionId !== null ? $this->factionCache->get($factionId) : null);
                return;
            }

            $this->inFlightPlayerFactionLoads[$playerXuid][] = $resolve;
        });
    }

    private function resolveInFlightPlayerFactionLoad(string $playerXuid, ?Faction $faction): void
    {
        $waiters = $this->inFlightPlayerFactionLoads[$playerXuid] ?? [];
        unset($this->inFlightPlayerFactionLoads[$playerXuid]);

        foreach ($waiters as $resolve) {
            try {
                $resolve($faction);
            } catch (Throwable) {
            }
        }
    }
}
