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

namespace Jorgebyte\Factions\utils;

enum Permissions: string
{
    case FACTIONS_COMMAND = "factions.command";
    case FACTIONS_COMMAND_CREATE = "factions.command.create";
    case FACTIONS_COMMAND_INFO = "factions.command.info";
    case FACTIONS_COMMAND_DISBAND = "factions.command.disband";
    case FACTIONS_COMMAND_INVITE = "factions.command.invite";
    case FACTIONS_COMMAND_ACCEPT = "factions.command.accept";
    case FACTIONS_COMMAND_DENY = "factions.command.deny";
    case FACTIONS_COMMAND_CLAIM = "factions.command.claim";
    case FACTIONS_COMMAND_UNCLAIM = "factions.command.unclaim";
    case FACTIONS_COMMAND_LEAVE = "factions.command.leave";
    case FACTIONS_COMMAND_KICK = "factions.command.kick";
    case FACTIONS_COMMAND_PROMOTE = "factions.command.promote";
    case FACTIONS_COMMAND_MONEY = "factions.command.money";
    case FACTIONS_COMMAND_DEPOSIT = "factions.command.deposit";
    case FACTIONS_COMMAND_WITHDRAW = "factions.command.withdraw";
    case FACTIONS_COMMAND_HOME = "factions.command.home";
    case FACTIONS_COMMAND_SETHOME = "factions.command.sethome";
    case FACTIONS_COMMAND_DELHOME = "factions.command.delhome";
    case FACTIONS_COMMAND_DEMOTE = "factions.command.demote";
    case FACTIONS_COMMAND_MAP = "factions.command.map";
    case FACTIONS_COMMAND_CHUNK = "factions.command.chunk";
    case FACTIONS_COMMAND_TOP = "factions.command.top";
    case FACTIONS_COMMAND_ALLY = "factions.command.ally";
    case FACTIONS_COMMAND_ENEMY = "factions.command.enemy";
    case FACTIONS_COMMAND_NEUTRAL = "factions.command.neutral";
    case FACTIONS_COMMAND_CHAT = "factions.command.chat";
    case FACTIONS_COMMAND_CACHE = "factions.command.cache";
    case FACTIONS_COMMAND_LEADER = "factions.command.leader"; // Transfer
    case FACTIONS_COMMAND_SETPOWER = "factions.command.setpower";
    case FACTIONS_COMMAND_ADDPOWER = "factions.command.addpower";
    case FACTIONS_COMMAND_REMOVEPOWER = "factions.command.removepower";
    case FACTIONS_COMMAND_FREEZEPOWER = "factions.command.freezepower";
    case FACTIONS_COMMAND_UNFREEZEPOWER = "factions.command.unfreezepower";
    case FACTIONS_COMMAND_PERM = "factions.command.perm";
    case FACTIONS_COMMAND_SUBCLAIM = "factions.command.subclaim";
    case FACTIONS_COMMAND_LOG = "factions.command.log";
}
