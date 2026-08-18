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

use Generator;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\event\faction\FactionTransferEvent;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;
use Throwable;

final readonly class LeadershipService
{
    public function __construct(
        private FactionManager $factionManager,
        private DataConnector  $connector,
    ) {
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function transferLeadership(Member $fromLeader, Member $toNewLeader): Generator
    {
        if ($fromLeader->factionId !== $toNewLeader->factionId) {
            return FactionResponse::fail(FactionResult::INVALID_REQUEST);
        }

        if ($fromLeader->playerXuid === $toNewLeader->playerXuid) {
            return FactionResponse::fail(FactionResult::INVALID_REQUEST);
        }

        $faction = $this->factionManager->getFactionCache()->get($fromLeader->factionId) ?? (yield from $this->factionManager->loadFaction($fromLeader->factionId));
        if ($faction === null) {
            return FactionResponse::fail(FactionResult::NOT_FOUND);
        }

        if ($faction->leaderXuid !== $fromLeader->playerXuid) {
            return FactionResponse::fail(FactionResult::INVALID_STATE);
        }

        $event = new FactionTransferEvent($faction, $fromLeader, $toNewLeader);
        $event->call();
        if ($event->isCancelled()) {
            return FactionResponse::fail(FactionResult::ACTION_CANCELLED);
        }

        $finalNewLeader = $event->getNewLeader();

        try {
            yield from Await::all([
                $this->connector->asyncChange("members.update_role", [
                    "faction_id" => $faction->id,
                    "player_xuid" => $fromLeader->playerXuid,
                    "role" => Role::COLEADER->value,
                ]),
                $this->connector->asyncChange("members.update_role", [
                    "faction_id" => $faction->id,
                    "player_xuid" => $finalNewLeader->playerXuid,
                    "role" => Role::LEADER->value,
                ]),
                $this->connector->asyncChange("factions.update_leader", [
                    "id" => $faction->id,
                    "leader_xuid" => $finalNewLeader->playerXuid,
                ]),
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $fromLeader->setRole(Role::COLEADER);
        $finalNewLeader->setRole(Role::LEADER);
        $faction->leaderXuid = $finalNewLeader->playerXuid;
        $this->factionManager->syncFactionDisplay($faction);

        return FactionResponse::success([
            'faction_id' => $faction->id,
            'leader_xuid' => $finalNewLeader->playerXuid,
        ]);
    }

    /**
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, FactionResponse>
     */
    public function promotePlayer(Member $member): Generator
    {
        $faction = $this->factionManager->getFactionCache()->get($member->factionId) ?? (yield from $this->factionManager->loadFaction($member->factionId));
        if ($faction === null) {
            return FactionResponse::fail(FactionResult::NOT_FOUND);
        }

        if ($member->getRole() !== Role::MEMBER) {
            return FactionResponse::fail(FactionResult::INVALID_STATE);
        }

        try {
            yield from $this->connector->asyncChange("members.update_role", [
                "faction_id" => $faction->id,
                "player_xuid" => $member->playerXuid,
                "role" => Role::COLEADER->value,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $member->setRole(Role::COLEADER);
        return FactionResponse::success([
            'faction_id' => $faction->id,
            'member_xuid' => $member->playerXuid,
        ]);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function demotePlayer(Member $member): Generator
    {
        $faction = $this->factionManager->getFactionCache()->get($member->factionId) ?? (yield from $this->factionManager->loadFaction($member->factionId));
        if ($faction === null) {
            return FactionResponse::fail(FactionResult::NOT_FOUND);
        }

        if ($member->getRole() !== Role::COLEADER) {
            return FactionResponse::fail(FactionResult::INVALID_STATE);
        }

        try {
            yield from $this->connector->asyncChange("members.update_role", [
                "faction_id" => $faction->id,
                "player_xuid" => $member->playerXuid,
                "role" => Role::MEMBER->value,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $member->setRole(Role::MEMBER);
        return FactionResponse::success([
            'faction_id' => $faction->id,
            'member_xuid' => $member->playerXuid,
        ]);
    }
}
