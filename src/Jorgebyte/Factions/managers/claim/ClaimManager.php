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

namespace Jorgebyte\Factions\managers\claim;

use Generator;
use Jorgebyte\Factions\cache\CachePriority;
use Jorgebyte\Factions\cache\ClaimMemoryCache;
use Jorgebyte\Factions\entities\Claim;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\event\claims\ChunkClaimEvent;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\FactionConfig;
use poggit\libasynql\DataConnector;
use Throwable;

final class ClaimManager
{
    private ClaimMemoryCache $cache;

    /** @var array<string, true> */
    private array $allowedWorldsLookup;

    /** @var array<string, true> */
    private array $inFlightChunkClaims = [];

    public function __construct(
        private readonly DataConnector $connector,
        private readonly FactionConfig $factionConfig,
        private readonly FactionManager $factionManager,
    ) {
        $this->cache = new ClaimMemoryCache();
        $this->allowedWorldsLookup = [];

        foreach ($this->factionConfig->getAllowedClaimWorlds() as $world) {
            if (!is_string($world) || $world === '') {
                continue;
            }
            $this->allowedWorldsLookup[strtolower($world)] = true;
        }
    }

    /**
     * @return Generator<mixed, mixed, mixed, void>
     */
    public function preloadClaims(): Generator
    {
        $rows = yield from $this->connector->asyncSelect("claims.get_all", []);
        foreach ($rows as $row) {
            $claim = new Claim(
                (int) $row['faction_id'],
                (int) $row['chunk_x'],
                (int) $row['chunk_z'],
                $row['world_name'],
            );
            $this->cache->set($claim, CachePriority::MEDIUM);
        }
    }

    public function getClaim(int $chunkX, int $chunkZ, string $worldName): ?Claim
    {
        return $this->cache->get($worldName, $chunkX, $chunkZ);
    }

    public function isWorldAllowed(string $worldName): bool
    {
        if ($this->allowedWorldsLookup === []) {
            return true;
        }

        return isset($this->allowedWorldsLookup[strtolower($worldName)]);
    }

    /**
     * @return Generator<mixed, mixed, mixed, ClaimResponse>
     */
    public function claimChunk(ClaimRequest $request, Faction $faction): Generator
    {
        $chunkX = $request->chunkX;
        $chunkZ = $request->chunkZ;
        $worldName = $request->worldName;

        if (!$this->isWorldAllowed($worldName)) {
            return ClaimResponse::fail(ClaimResult::WORLD_NOT_ALLOWED, ['world' => $worldName]);
        }

        if ($this->factionConfig->isSpawnProtectionEnabled()) {
            $spawnRadius = $this->factionConfig->getSpawnProtectionRadius();
            if ($spawnRadius > 0) {
                $distance = max(abs($chunkX - $request->spawnChunkX), abs($chunkZ - $request->spawnChunkZ));

                if ($distance <= $spawnRadius) {
                    return ClaimResponse::fail(ClaimResult::TOO_CLOSE_TO_SPAWN, ['distance' => $distance, 'radius' => $spawnRadius]);
                }
            }
        }

        $existingClaim = $this->getClaim($chunkX, $chunkZ, $worldName);
        $isOverclaim = false;
        $previousOwner = null;

        if ($existingClaim !== null) {
            if ($existingClaim->factionId === $faction->id) {
                return ClaimResponse::fail(ClaimResult::ALREADY_CLAIMED, ['owner_faction_id' => $existingClaim->factionId]);
            }

            $ownerFaction = $this->factionManager->getLoadedFactionById($existingClaim->factionId);
            if ($ownerFaction !== null && $ownerFaction->isRaidable()) {
                $isOverclaim = true;
                $previousOwner = $ownerFaction;
            } else {
                return ClaimResponse::fail(ClaimResult::ALREADY_CLAIMED, ['owner_faction_id' => $existingClaim->factionId]);
            }
        }

        $cost = $this->factionConfig->getClaimCost();
        if ($faction->money < $cost) {
            return ClaimResponse::fail(ClaimResult::NOT_ENOUGH_MONEY, [
                'required' => $cost,
                'available' => $faction->money,
            ]);
        }

        if ($faction->getClaimsCount() >= $this->factionConfig->getMaxClaimsPerFaction()) {
            return ClaimResponse::fail(ClaimResult::MAX_LIMIT_REACHED, [
                'max' => $this->factionConfig->getMaxClaimsPerFaction(),
                'current' => $faction->getClaimsCount(),
            ]);
        }

        $inFlightKey = $this->buildChunkKey($worldName, $chunkX, $chunkZ);
        if (isset($this->inFlightChunkClaims[$inFlightKey])) {
            return ClaimResponse::fail(ClaimResult::ALREADY_CLAIMED, ['in_progress' => true]);
        }
        $this->inFlightChunkClaims[$inFlightKey] = true;

        try {
            $event = new ChunkClaimEvent(
                $faction,
                $chunkX,
                $chunkZ,
                $worldName,
                $request->claimerXuid,
                $request->claimerName,
            );
            $event->call();
            if ($event->isCancelled()) {
                return ClaimResponse::fail(ClaimResult::ACTION_CANCELLED);
            }

            try {
                if ($isOverclaim) {
                    yield from $this->connector->asyncChange("claims.delete", [
                        "chunk_x" => $chunkX,
                        "chunk_z" => $chunkZ,
                        "world_name" => $worldName,
                    ]);
                    $previousOwner->removeClaim($this->buildChunkKey($worldName, $chunkX, $chunkZ));
                    $this->cache->remove($worldName, $chunkX, $chunkZ);
                }

                $changed = yield from $this->connector->asyncChange("claims.insert", [
                    "faction_id" => $faction->id,
                    "chunk_x" => $chunkX,
                    "chunk_z" => $chunkZ,
                    "world_name" => $worldName,
                    "claimed_at" => time(),
                ]);

                if ($changed <= 0) {
                    return ClaimResponse::fail(ClaimResult::ALREADY_CLAIMED);
                }

                $faction->removeMoney($cost);
                $this->factionManager->updateFactionMoney($faction);
            } catch (Throwable) {
                try {
                    yield from $this->connector->asyncChange("claims.delete", [
                        "chunk_x" => $chunkX,
                        "chunk_z" => $chunkZ,
                        "world_name" => $worldName,
                    ]);
                } catch (Throwable) {
                    // Best-effort rollback
                }
                return ClaimResponse::fail(ClaimResult::INTERNAL_ERROR);
            }
        } finally {
            unset($this->inFlightChunkClaims[$inFlightKey]);
        }

        $claim = new Claim($faction->id, $chunkX, $chunkZ, $worldName);
        $this->cache->set($claim, CachePriority::HIGH);
        $faction->addClaim($claim);
        $this->factionManager->syncFactionDisplay($faction);

        return ClaimResponse::success([
            'faction_id' => $faction->id,
            'chunk_x' => $chunkX,
            'chunk_z' => $chunkZ,
            'world' => $worldName,
            'cost' => $cost,
            'result' => $isOverclaim ? ClaimResult::OVERCLAIM_SUCCESS : ClaimResult::SUCCESS,
        ]);
    }

    /**
     * @return Generator<mixed, mixed, mixed, ClaimResponse>
     */
    public function unclaimChunk(UnclaimRequest $request, Faction $faction): Generator
    {
        $chunkX = $request->chunkX;
        $chunkZ = $request->chunkZ;
        $worldName = $request->worldName;

        $claim = $this->getClaim($chunkX, $chunkZ, $worldName);

        if ($claim === null) {
            return ClaimResponse::fail(ClaimResult::UNCLAIM_NOT_CLAIMED);
        }

        if ($claim->factionId !== $faction->id) {
            return ClaimResponse::fail(ClaimResult::UNCLAIM_WRONG_FACTION, ['owner_faction_id' => $claim->factionId]);
        }

        try {
            yield from $this->connector->asyncChange("claims.delete", [
                "chunk_x" => $chunkX,
                "chunk_z" => $chunkZ,
                "world_name" => $worldName,
            ]);
        } catch (Throwable) {
            return ClaimResponse::fail(ClaimResult::INTERNAL_ERROR);
        }

        $this->cache->remove($worldName, $chunkX, $chunkZ);
        $faction->removeClaim($claim->getChunkKey());
        $this->factionManager->syncFactionDisplay($faction);

        return ClaimResponse::success([
            'faction_id' => $faction->id,
            'chunk_x' => $chunkX,
            'chunk_z' => $chunkZ,
            'world' => $worldName,
        ]);
    }

    public function purgeFactionClaims(int $factionId): void
    {
        $this->cache->removeByFactionId($factionId);
    }

    public function getClaimCache(): ClaimMemoryCache
    {
        return $this->cache;
    }

    private function buildChunkKey(string $worldName, int $chunkX, int $chunkZ): string
    {
        return strtolower($worldName) . ':' . $chunkX . ':' . $chunkZ;
    }
}
