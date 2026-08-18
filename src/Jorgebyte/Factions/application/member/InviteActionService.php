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

namespace Jorgebyte\Factions\application\member;

use Generator;
use Jorgebyte\Factions\application\shared\CommandResult;
use Jorgebyte\Factions\entities\enums\FactionPermission;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\AddMemberRequest;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\faction\FactionResult;
use Jorgebyte\Factions\managers\invite\InviteManager;
use pocketmine\player\Player;

final readonly class InviteActionService
{
    public function __construct(
        private FactionManager $factionManager,
        private InviteManager $inviteManager,
    ) {
    }

    public function precheckInvite(Player $sender, Player $invitedPlayer): InvitePrecheckResult
    {
        if ($invitedPlayer->getId() === $sender->getId()) {
            return InvitePrecheckResult::fail(new CommandResult(LangKeys::INVITE_SELF));
        }

        $faction = $this->factionManager->getPlayerFaction($sender->getXuid());
        if ($faction === null) {
            return InvitePrecheckResult::fail(new CommandResult(LangKeys::GENERIC_NOT_IN_FACTION));
        }

        $member = $faction->getMember($sender->getXuid());
        if ($member === null || !$faction->permissions->hasPermission($member->getRole(), FactionPermission::INVITE)) {
            return InvitePrecheckResult::fail(new CommandResult(LangKeys::INVITE_NO_PERMS));
        }

        $maxMembers = $this->factionManager->getFactionConfig()->getMaxMembersPerFaction();
        $memberCount = $faction->getMembersCount();
        if ($memberCount >= $maxMembers) {
            return InvitePrecheckResult::fail(new CommandResult(LangKeys::INVITE_FACTION_FULL, [
                '{faction}' => $faction->name,
                '{count}' => $memberCount,
                '{max}' => $maxMembers,
            ]));
        }

        if ($this->factionManager->getPlayerFaction($invitedPlayer->getXuid()) !== null) {
            return InvitePrecheckResult::fail(new CommandResult(LangKeys::INVITE_ALREADY_IN_FACTION, [
                '{player}' => $invitedPlayer->getName(),
            ]));
        }

        return InvitePrecheckResult::success($faction);
    }

    public function invite(Player $sender, Player $invitedPlayer, Faction $faction): CommandResult
    {
        $result = $this->inviteManager->addInvite(
            $faction->id,
            $sender->getXuid(),
            $invitedPlayer->getXuid(),
        );

        $messageKey = InviteResultLangMapper::forInvite($result);

        return new CommandResult($messageKey, ['{player}' => $invitedPlayer->getName()]);
    }

    /**
     * @throws \Throwable
     * @return Generator<mixed, mixed, mixed, CommandResult>
     */
    public function accept(Player $player): Generator
    {
        if ($this->factionManager->getPlayerFaction($player->getXuid()) !== null) {
            return new CommandResult(LangKeys::CREATE_ALREADY_IN_FACTION);
        }

        $inviteFactionId = $this->inviteManager->getInvite($player->getXuid());
        if ($inviteFactionId === null) {
            return new CommandResult(LangKeys::ACCEPT_NO_INVITE);
        }

        if ($inviteFactionId <= 0) {
            return new CommandResult(LangKeys::ACCEPT_CANCELLED);
        }

        $faction = yield from $this->factionManager->loadFaction($inviteFactionId);
        if ($faction === null) {
            return new CommandResult(LangKeys::ACCEPT_CANCELLED);
        }

        $max = $this->factionManager->getFactionConfig()->getMaxMembersPerFaction();
        $count = $faction->getMembersCount();
        if ($count >= $max) {
            return $this->buildFactionFullResult($faction, $count, $max);
        }

        $joinResponse = yield from $this->factionManager->addMemberToFactionResponse(new AddMemberRequest(
            $inviteFactionId,
            $player->getXuid(),
            $player->getName(),
        ));
        if ($joinResponse->isSuccess()) {
            $this->inviteManager->removeInvite($player->getXuid());
            return new CommandResult(LangKeys::ACCEPT_SUCCESS);
        }

        $updatedCount = $faction->getMembersCount();
        if ($joinResponse->result === FactionResult::FACTION_FULL || $updatedCount >= $max) {
            return $this->buildFactionFullResult($faction, $updatedCount, $max);
        }

        return new CommandResult(LangKeys::ACCEPT_CANCELLED);
    }

    public function deny(Player $player): CommandResult
    {
        $inviteFactionId = $this->inviteManager->getInvite($player->getXuid());
        if ($inviteFactionId === null) {
            return new CommandResult(LangKeys::DENY_NO_INVITE);
        }

        $this->inviteManager->removeInvite($player->getXuid());
        return new CommandResult(LangKeys::DENY_SUCCESS);
    }

    private function buildFactionFullResult(Faction $faction, int $count, int $max): CommandResult
    {
        return new CommandResult(LangKeys::ACCEPT_FACTION_FULL, [
            '{faction}' => $faction->name,
            '{count}' => $count,
            '{max}' => $max,
        ]);
    }
}
