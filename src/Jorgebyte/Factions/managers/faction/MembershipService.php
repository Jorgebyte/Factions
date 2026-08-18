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
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\event\faction\FactionDisbandEvent;
use Jorgebyte\Factions\event\member\MemberJoinEvent;
use Jorgebyte\Factions\event\member\MemberKickEvent;
use Jorgebyte\Factions\event\member\MemberLeaveEvent;
use Jorgebyte\Factions\utils\PlayerUtils;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class MembershipService
{
    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly DataConnector $connector,
    ) {
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function addMemberToFaction(AddMemberRequest $request): Generator
    {
        $factionId = $request->factionId;
        $faction = $this->factionManager->getFactionCache()->get($factionId) ?? (yield from $this->factionManager->loadFaction($factionId));
        if ($faction === null) {
            return FactionResponse::fail(FactionResult::NOT_FOUND);
        }

        if ($faction->getMember($request->memberXuid) !== null) {
            return FactionResponse::fail(FactionResult::INVALID_STATE);
        }

        $maxMembers = $this->factionManager->getFactionConfig()->getMaxMembersPerFaction();
        if ($faction->getMembersCount() >= $maxMembers) {
            return FactionResponse::fail(FactionResult::FACTION_FULL, [
                'max_members' => $maxMembers,
            ]);
        }

        $joiningPlayer = PlayerUtils::getPlayerByXuid($request->memberXuid);
        if ($joiningPlayer !== null) {
            $event = new MemberJoinEvent($faction, $joiningPlayer);
            $event->call();
            if ($event->isCancelled()) {
                return FactionResponse::fail(FactionResult::ACTION_CANCELLED);
            }
        }

        $role = Role::MEMBER;
        try {
            yield from $this->connector->asyncChange("members.insert", [
                "faction_id" => $factionId,
                "player_xuid" => $request->memberXuid,
                "player_name" => $request->memberName,
                "role" => $role->value,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $faction->addMember(new Member($factionId, $request->memberXuid, $request->memberName, $role));
        $this->factionManager->setPlayerFactionMapping($request->memberXuid, $factionId);
        $this->factionManager->syncPlayerDisplayByXuid($request->memberXuid);
        $this->factionManager->syncFactionDisplay($faction);

        return FactionResponse::success([
            'faction_id' => $factionId,
            'member_xuid' => $request->memberXuid,
        ]);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function leaveFaction(Member $member): Generator
    {
        $faction = $this->factionManager->getFactionCache()->get($member->factionId) ?? (yield from $this->factionManager->loadFaction($member->factionId));
        if ($faction === null) {
            return FactionResponse::fail(FactionResult::NOT_FOUND);
        }

        (new MemberLeaveEvent($faction, $member))->call();

        try {
            yield from $this->connector->asyncChange("members.delete", [
                "faction_id" => $member->factionId,
                "player_xuid" => $member->playerXuid,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $this->factionManager->clearPlayerFactionMapping($member->playerXuid);
        $faction->removeMember($member->playerXuid);
        $this->factionManager->clearPlayerDisplayByXuid($member->playerXuid);
        $this->factionManager->syncFactionDisplay($faction);

        return FactionResponse::success([
            'faction_id' => $faction->id,
            'member_xuid' => $member->playerXuid,
        ]);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function kickMember(KickMemberRequest $request): Generator
    {
        $memberToKick = $request->memberToKick;
        $faction = $this->factionManager->getFactionCache()->get($memberToKick->factionId) ?? (yield from $this->factionManager->loadFaction($memberToKick->factionId));
        if ($faction === null) {
            return FactionResponse::fail(FactionResult::NOT_FOUND);
        }

        $kickerPlayer = PlayerUtils::getPlayerByXuid($request->actorXuid);
        if ($kickerPlayer !== null) {
            $event = new MemberKickEvent($faction, $memberToKick, $kickerPlayer);
            $event->call();
            if ($event->isCancelled()) {
                return FactionResponse::fail(FactionResult::ACTION_CANCELLED);
            }
        }

        try {
            yield from $this->connector->asyncChange("members.delete", [
                "faction_id" => $memberToKick->factionId,
                "player_xuid" => $memberToKick->playerXuid,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $this->factionManager->clearPlayerFactionMapping($memberToKick->playerXuid);
        $faction->removeMember($memberToKick->playerXuid);
        $this->factionManager->clearPlayerDisplayByXuid($memberToKick->playerXuid);
        $this->factionManager->syncFactionDisplay($faction);

        return FactionResponse::success([
            'faction_id' => $faction->id,
            'member_xuid' => $memberToKick->playerXuid,
        ]);
    }

    /** @return Generator<mixed, mixed, mixed, FactionResponse> */
    public function disbandFaction(Faction $faction): Generator
    {
        $event = new FactionDisbandEvent($faction);
        $event->call();
        if ($event->isCancelled()) {
            return FactionResponse::fail(FactionResult::ACTION_CANCELLED);
        }

        $factionId = $faction->id;
        $members = $faction->getMembers();

        try {
            yield from Await::all([
                $this->connector->asyncChange("alliances.delete_all_for_faction", ["faction_id" => $factionId]),
                $this->connector->asyncChange("claims.delete_all_from_faction", ["faction_id" => $factionId]),
                $this->connector->asyncChange("members.delete_all_from_faction", ["faction_id" => $factionId]),
                $this->connector->asyncChange("subclaims.delete_all_from_faction", ["faction_id" => $factionId]),
                $this->connector->asyncChange("faction_permissions.delete_all_from_faction", ["faction_id" => $factionId]),
                $this->connector->asyncChange("factions.delete", ["id" => $factionId]),
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }

        $this->factionManager->purgeFactionClaims($factionId);
        $this->factionManager->removeFactionFromCache($factionId);

        $cooldownPromises = [];
        foreach ($members as $member) {
            $this->factionManager->clearPlayerFactionMapping($member->playerXuid);
            $cooldownPromises[] = $this->factionManager->setPlayerDisbandCooldown($member->playerXuid);
        }
        if (!empty($cooldownPromises)) {
            yield from Await::all($cooldownPromises);
        }

        $this->factionManager->clearFactionDisplay($faction);
        $this->factionManager->invalidateTopCache();

        return FactionResponse::success([
            'faction_id' => $factionId,
        ]);
    }
}
