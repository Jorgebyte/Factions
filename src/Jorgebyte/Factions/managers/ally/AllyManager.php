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

namespace Jorgebyte\Factions\managers\ally;

use Generator;
use Jorgebyte\Factions\cache\AllyMemoryCache;
use Jorgebyte\Factions\entities\Alliance;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\event\ally\AllyAddEvent;
use Jorgebyte\Factions\event\ally\AllyRemoveEvent;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\FactionConfig;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class AllyManager
{
    private AllyMemoryCache $cache;

    public function __construct(
        private readonly DataConnector $connector,
        private readonly FactionConfig $factionConfig,
        private readonly FactionManager $factionManager,
    ) {
        $this->cache = new AllyMemoryCache();
    }

    /**
     * @return Generator<mixed, mixed, mixed, void>
     */
    public function preloadAlliances(): Generator
    {
        $this->cache->clear();
        $rows = yield from $this->connector->asyncSelect("alliances.get_all_accepted");

        foreach ($rows as $row) {
            $this->cache->add((int) $row['faction_id'], (int) $row['ally_id']);
        }

        $this->cache->markHydrated();
    }

    /**
     * @return Generator<mixed, mixed, mixed, Alliance[]>
     */
    public function getAlliancesForFaction(int $factionId): Generator
    {
        if ($this->cache->isHydrated()) {
            $alliances = [];
            foreach ($this->cache->getAlliedFactionIds($factionId) as $allyId) {
                $alliances[] = new Alliance($factionId, $allyId, 'accepted');
            }
            return $alliances;
        }

        $rows = yield from $this->connector->asyncSelect("alliances.get_by_faction", [
            "faction_id" => $factionId,
        ]);

        $alliances = [];
        foreach ($rows as $row) {
            if ($row["status"] !== "accepted") {
                continue;
            }

            $alliances[] = new Alliance(
                (int) $row["faction_id"],
                (int) $row["ally_id"],
                $row["status"],
            );
        }

        return $alliances;
    }

    /** @return Generator<mixed, mixed, mixed, AllyResponse> */
    public function canFormMoreAlliances(int $factionId): Generator
    {
        if ($factionId <= 0) {
            return AllyResponse::fail(AllyResult::INVALID_REQUEST);
        }

        try {
            if ($this->cache->isHydrated()) {
                $current = $this->cache->countAlliesForFaction($factionId);
            } else {
                $alliances = yield from $this->getAlliancesForFaction($factionId);
                $current = count($alliances);
            }

            $max = $this->factionConfig->getMaxAlliancesPerFaction();
            if ($current >= $max) {
                return AllyResponse::fail(AllyResult::MAX_ALLIANCES_REACHED, [
                    'current' => $current,
                    'max' => $max,
                    'faction_id' => $factionId,
                ]);
            }

            return AllyResponse::success([
                'current' => $current,
                'max' => $max,
                'faction_id' => $factionId,
            ]);
        } catch (Throwable) {
            return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
        }
    }

    /** @return Generator<mixed, mixed, mixed, AllyResponse> */
    public function sendAllyRequest(SendAllyRequest $request): Generator
    {
        $fromFactionId = $request->fromFactionId;
        $toFactionId = $request->toFactionId;

        if ($fromFactionId <= 0 || $toFactionId <= 0) {
            return AllyResponse::fail(AllyResult::INVALID_REQUEST);
        }

        if ($fromFactionId === $toFactionId) {
            return AllyResponse::fail(AllyResult::SELF_TARGET);
        }

        if (!in_array($request->senderRole, [Role::LEADER, Role::COLEADER], true)) {
            return AllyResponse::fail(AllyResult::NO_PERMISSION);
        }

        if ($this->cache->areAllied($fromFactionId, $toFactionId)) {
            return AllyResponse::fail(AllyResult::ALREADY_ALLIED);
        }

        $canFormFrom = yield from $this->canFormMoreAlliances($fromFactionId);
        $canFormTo = yield from $this->canFormMoreAlliances($toFactionId);

        if (!$canFormFrom->isSuccess() || !$canFormTo->isSuccess()) {
            return AllyResponse::fail(AllyResult::MAX_ALLIANCES_REACHED);
        }

        $timeout = $this->factionConfig->getAllyRequestTimeout();
        try {
            $existing = yield from $this->connector->asyncSelect("alliances.get_status", [
                "faction_id" => $fromFactionId,
                "ally_id" => $toFactionId,
            ]);
        } catch (Throwable) {
            return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
        }

        if (!empty($existing)) {
            if (($existing[0]['status'] ?? '') === 'accepted') {
                return AllyResponse::fail(AllyResult::ALREADY_ALLIED);
            }

            $created = (int) ($existing[0]['created_at'] ?? 0);
            if (time() - $created < $timeout) {
                return AllyResponse::fail(AllyResult::ALREADY_PENDING);
            }

            try {
                yield from $this->connector->asyncChange("alliances.delete", [
                    "faction_id" => $fromFactionId,
                    "ally_id" => $toFactionId,
                ]);
            } catch (Throwable) {
                return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
            }
        }

        try {
            yield from $this->connector->asyncChange("alliances.insert", [
                "faction_id" => $fromFactionId,
                "ally_id" => $toFactionId,
                "status" => "pending",
                "created_at" => time(),
            ]);
        } catch (Throwable) {
            return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
        }

        return AllyResponse::success([
            'from_faction_id' => $fromFactionId,
            'to_faction_id' => $toFactionId,
        ]);
    }

    /**
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, AllyResponse>
     */
    public function acceptAllyRequest(AcceptAllyRequest $request): Generator
    {
        if ($request->fromFactionId <= 0 || $request->toFactionId <= 0) {
            return AllyResponse::fail(AllyResult::INVALID_REQUEST);
        }

        if (!in_array($request->accepterRole, [Role::LEADER, Role::COLEADER], true)) {
            return AllyResponse::fail(AllyResult::NO_PERMISSION);
        }

        $fromFactionId = $request->fromFactionId;
        $toFactionId = $request->toFactionId;

        $timeout = $this->factionConfig->getAllyRequestTimeout();
        try {
            $existing = yield from $this->connector->asyncSelect("alliances.get_status", [
                "faction_id" => $fromFactionId,
                "ally_id" => $toFactionId,
            ]);
        } catch (Throwable) {
            return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
        }

        if (empty($existing) || $existing[0]['status'] !== 'pending') {
            return AllyResponse::fail(AllyResult::REQUEST_NOT_FOUND);
        }

        $created = (int) ($existing[0]['created_at'] ?? 0);
        if (time() - $created >= $timeout) {
            try {
                yield from $this->connector->asyncChange("alliances.delete", [
                    "faction_id" => $fromFactionId,
                    "ally_id" => $toFactionId,
                ]);
            } catch (Throwable) {
                return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
            }

            return AllyResponse::fail(AllyResult::REQUEST_EXPIRED);
        }

        $fromFaction = yield from $this->factionManager->loadFaction($fromFactionId);
        $toFaction = yield from $this->factionManager->loadFaction($toFactionId);

        if ($fromFaction === null || $toFaction === null) {
            return AllyResponse::fail(AllyResult::FACTION_NOT_FOUND);
        }

        $event = new AllyAddEvent($fromFaction, $toFaction);
        $event->call();
        if ($event->isCancelled()) {
            return AllyResponse::fail(AllyResult::ACTION_CANCELLED);
        }

        try {
            yield from Await::all([
                $this->connector->asyncChange("alliances.update_status", [
                    "faction_id" => $fromFactionId,
                    "ally_id" => $toFactionId,
                    "status" => "accepted",
                ]),
                $this->connector->asyncChange("alliances.insert", [
                    "faction_id" => $toFactionId,
                    "ally_id" => $fromFactionId,
                    "status" => "accepted",
                    "created_at" => time(),
                ]),
            ]);
        } catch (Throwable) {
            return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
        }

        $this->cache->add($fromFactionId, $toFactionId);
        $this->cache->add($toFactionId, $fromFactionId);
        $fromFaction->addAlly($toFactionId);
        $toFaction->addAlly($fromFactionId);
        $this->factionManager->syncFactionDisplay($fromFaction);
        $this->factionManager->syncFactionDisplay($toFaction);

        return AllyResponse::success([
            'from_faction_id' => $fromFactionId,
            'to_faction_id' => $toFactionId,
        ]);
    }

    /**
     *  @throws Throwable
     * @return Generator<mixed, mixed, mixed, AllyResponse>
     */
    public function removeAlly(RemoveAllyRequest $request): Generator
    {
        $factionId = $request->factionId;
        $allyId = $request->allyId;
        if ($factionId <= 0 || $allyId <= 0) {
            return AllyResponse::fail(AllyResult::INVALID_REQUEST);
        }

        if ($factionId === $allyId) {
            return AllyResponse::fail(AllyResult::SELF_TARGET);
        }

        $isAllied = $this->cache->areAllied($factionId, $allyId);

        $faction = null;
        $allyFaction = null;
        if ($isAllied) {
            $faction = yield from $this->factionManager->loadFaction($factionId);
            $allyFaction = yield from $this->factionManager->loadFaction($allyId);

            if ($faction === null || $allyFaction === null) {
                return AllyResponse::fail(AllyResult::FACTION_NOT_FOUND);
            }

            $event = new AllyRemoveEvent($faction, $allyFaction);
            $event->call();
            if ($event->isCancelled()) {
                return AllyResponse::fail(AllyResult::ACTION_CANCELLED);
            }
        }

        try {
            [$changedForward, $changedBackward] = yield from Await::all([
                $this->connector->asyncChange("alliances.delete", ["faction_id" => $factionId, "ally_id" => $allyId]),
                $this->connector->asyncChange("alliances.delete", ["faction_id" => $allyId, "ally_id" => $factionId]),
            ]);
        } catch (Throwable) {
            return AllyResponse::fail(AllyResult::INTERNAL_ERROR);
        }

        if (!$isAllied && (int) $changedForward === 0 && (int) $changedBackward === 0) {
            return AllyResponse::fail(AllyResult::REQUEST_NOT_FOUND);
        }

        if ($isAllied) {
            $faction->removeAlly($allyId);
            $allyFaction->removeAlly($factionId);
            $this->factionManager->syncFactionDisplay($faction);
            $this->factionManager->syncFactionDisplay($allyFaction);
        }

        $this->cache->remove($factionId, $allyId);
        $this->cache->remove($allyId, $factionId);
        return AllyResponse::success([
            'faction_id' => $factionId,
            'ally_id' => $allyId,
        ]);
    }

    public function areAllied(int $factionId, int $allyId): bool
    {
        return $this->cache->areAllied($factionId, $allyId);
    }
}
