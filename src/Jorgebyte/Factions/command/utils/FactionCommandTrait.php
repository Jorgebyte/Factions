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

namespace Jorgebyte\Factions\command\utils;

use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use pocketmine\player\Player;

trait FactionCommandTrait
{
    protected function getFactionOrMessage(Player $player, FactionManager $manager, string $messageKey): ?Faction
    {
        $faction = $manager->getPlayerFaction($player->getXuid());
        if ($faction === null) {
            $player->sendMessage(Lang::t($player, $messageKey));
            return null;
        }
        return $faction;
    }

    /**
     * @param Role[] $allowedRoles
     */
    protected function checkRoleOrMessage(Player $player, Faction $faction, array $allowedRoles, string $messageKey): bool
    {
        $member = $faction->getMember($player->getXuid());
        if ($member === null || !in_array($member->getRole(), $allowedRoles, true)) {
            $player->sendMessage(Lang::t($player, $messageKey));
            return false;
        }
        return true;
    }

    protected function ensureNotInFaction(Player $player, FactionManager $manager, string $messageKey): bool
    {
        if ($manager->getPlayerFaction($player->getXuid()) !== null) {
            $player->sendMessage(Lang::t($player, $messageKey));
            return false;
        }
        return true;
    }

    protected function getMemberByNameOrMessage(Player $player, Faction $faction, string $targetName, string $messageKey): ?Member
    {
        $targetMember = $faction->getMemberByName($targetName);
        if ($targetMember === null) {
            $player->sendMessage(Lang::t($player, $messageKey, ["player" => $targetName]));
            return null;
        }
        return $targetMember;
    }

    protected function validateTargetNotSelf(Player $sender, Member $targetMember, string $messageKey): bool
    {
        if ($targetMember->playerXuid === $sender->getXuid()) {
            $sender->sendMessage(Lang::t($sender, $messageKey));
            return false;
        }
        return true;
    }

    protected function validateTargetRole(Player $sender, Member $targetMember, Role $role, string $messageKey, bool $shouldHaveRole = false): bool
    {
        if ($shouldHaveRole) {
            if ($targetMember->getRole() !== $role) {
                $sender->sendMessage(Lang::t($sender, $messageKey));
                return false;
            }
        } else {
            if ($targetMember->getRole() === $role) {
                $sender->sendMessage(Lang::t($sender, $messageKey));
                return false;
            }
        }
        return true;
    }

    protected function getMemberOrMessage(Faction $faction, string $name, Player $sender): ?Member
    {
        $member = $faction->getMemberByName($name);
        if ($member === null) {
            $sender->sendMessage(Lang::t($sender, LangKeys::GENERIC_PLAYER_NOT_FOUND->value));
            return null;
        }
        return $member;
    }

    protected function validateTargetHierarchy(Player $sender, Faction $faction, Member $target, bool $canTargetEquals = false): bool
    {
        $senderMember = $faction->getMember($sender->getXuid());
        if ($senderMember === null) {
            return false;
        }

        if ($senderMember->getRole()->value < $target->getRole()->value) {
            return false;
        }

        if (!$canTargetEquals && $senderMember->getRole()->value === $target->getRole()->value) {
            return false;
        }

        return true;
    }
}
