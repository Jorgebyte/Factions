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

namespace Jorgebyte\Factions\entities\enums;

enum FactionPermission: string
{
    case BREAK = "break";
    case PLACE = "place";
    case CONTAINERS = "containers";
    case INTERACT = "interact";
    case BANK_DEPOSIT = "bank_deposit";
    case BANK_WITHDRAW = "bank_withdraw";
    case INVITE = "invite";
    case KICK = "kick";
    case PROMOTE = "promote";
    case DEMOTE = "demote";
    case SET_HOME = "set_home";
    case CLAIM = "claim";
    case UNCLAIM = "unclaim";
    case SUBCLAIM = "subclaim";
    case ALLIES = "allies";

    public function getDescription(): string
    {
        return match($this) {
            self::BREAK => "Break blocks in claim",
            self::PLACE => "Place blocks in claim",
            self::CONTAINERS => "Open chests, barrels, hoppers, furnaces",
            self::INTERACT => "Use doors, trapdoors, levers, buttons",
            self::BANK_DEPOSIT => "Deposit money to faction bank",
            self::BANK_WITHDRAW => "Withdraw money from faction bank",
            self::INVITE => "Invite new members",
            self::KICK => "Kick lower-ranked members",
            self::PROMOTE => "Promote members",
            self::DEMOTE => "Demote members",
            self::SET_HOME => "Set/delete faction home",
            self::CLAIM => "Claim land chunks",
            self::UNCLAIM => "Unclaim land chunks",
            self::SUBCLAIM => "Create/remove subclaims",
            self::ALLIES => "Manage alliances",
        };
    }
}
