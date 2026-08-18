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

namespace Jorgebyte\Factions\lang;

enum LangKeys: string
{
    // --- Generic Messages ---
    case GENERIC_PLAYER_NOT_FOUND = "generic.player_not_found";
    case GENERIC_NOT_IN_FACTION = "generic.not_in_faction";
    case GENERIC_NONE = "generic.none";
    case ACTION_CANCELLED_BY_EVENT = "generic.action_cancelled";
    case FACTIONS_COMMAND_USAGE = "command.factions.usage";

    // --- Create Command ---
    case CREATE_NEED_NAME = "command.create.need_name";
    case CREATE_ALREADY_IN_FACTION = "command.create.already_in_faction";
    case CREATE_NAME_TAKEN = "command.create.name_taken";
    case CREATE_SUCCESS = "command.create.success";
    case CREATE_CANCELLED = "command.create.cancelled";
    case CREATE_COOLDOWN = "command.create.cooldown";

    // --- Disband Command ---
    case DISBAND_NOT_LEADER = "command.disband.not_leader";
    case DISBAND_SUCCESS = "command.disband.success";
    case DISBAND_CANCELLED = "command.disband.cancelled";

    // --- Info Command ---
    case INFO_FACTION_NOT_FOUND = "command.info.faction_not_found";
    case INFO_USAGE = "command.info.usage";
    case INFO_NO_FACTION = "command.info.no_faction";
    case INFO_MEMBER_ONLINE_PREFIX = "command.info.member_online_prefix";
    case INFO_MEMBER_OFFLINE_PREFIX = "command.info.member_offline_prefix";
    case GET_INFO = "command.info.get_info";

    // --- Top Command ---
    case TOP_EMPTY = "command.top.empty";
    case TOP_INVALID_PAGE = "command.top.invalid_page";
    case TOP_HEADER = "command.top.header";
    case TOP_ENTRY = "command.top.entry";
    case TOP_TYPE_POWER = "command.top.type.power";
    case TOP_TYPE_KILLS = "command.top.type.kills";
    case TOP_TYPE_MONEY = "command.top.type.money";

    // --- Relation Command ---
    case RELATION_NOT_IN_FACTION = "command.relation.not_in_faction";
    case RELATION_TARGET_NOT_FOUND = "command.relation.target_not_found";
    case RELATION_CANNOT_RELATION_SELF = "command.relation.cannot_relation_self";
    case RELATION_ALLY_ACCEPTED_SELF = "command.relation.ally_accepted_self";
    case RELATION_ALREADY_RELATION = "command.relation.already_relation";
    case RELATION_ALLY_REQUEST_SENT = "command.relation.ally_request_sent";
    case RELATION_LIMIT_REACHED = "command.relation.limit_reached";
    case RELATION_NO_PERMISSION = "command.relation.no_permission";
    case RELATION_NEUTRAL_SET_SELF = "command.relation.neutral_set_self";
    case RELATION_UPDATE_FAILED = "command.relation.update_failed";
    case RELATION_ALLY_USAGE = "command.relation.ally_usage";

    // --- Invite Command ---
    case INVITE_SELF = "command.invite.self";
    case INVITE_NO_PERMS = "command.invite.no_perms";
    case INVITE_ALREADY_IN_FACTION = "command.invite.already_in_faction";
    case INVITE_FACTION_FULL = "command.invite.faction_full";
    case INVITE_SUCCESS_SENDER = "command.invite.success_sender";
    case INVITE_SUCCESS_RECEIVER = "command.invite.success_receiver";
    case INVITE_CANCELLED = "command.invite.cancelled";

    // --- Accept Command ---
    case ACCEPT_NO_INVITE = "command.accept.no_invite";
    case ACCEPT_SUCCESS = "command.accept.success";
    case ACCEPT_CANCELLED = "command.accept.cancelled";
    case ACCEPT_FACTION_FULL = "command.accept.faction_full";

    // --- Deny Command ---
    case DENY_NO_INVITE = "command.deny.no_invite";
    case DENY_SUCCESS = "command.deny.success";

    // --- Claim Command ---
    case CLAIM_NOT_IN_FACTION = "command.claim.not_in_faction";
    case CLAIM_NO_PERMISSION = "command.claim.no_permission";
    case CLAIM_SUCCESS = "command.claim.success";
    case CLAIM_FAILED = "command.claim.failed";
    case CLAIM_WORLD_NOT_ALLOWED = "command.claim.world_not_allowed";
    case CLAIM_TOO_CLOSE_TO_SPAWN = "command.claim.too_close_to_spawn";
    case CLAIM_ALREADY_CLAIMED = "command.claim.already_claimed";
    case CLAIM_NOT_ENOUGH_MONEY = "command.claim.not_enough_money";
    case CLAIM_MAX_LIMIT_REACHED = "command.claim.max_limit_reached";

    // --- Unclaim Command ---
    case UNCLAIM_NOT_IN_FACTION = "command.unclaim.not_in_faction";
    case UNCLAIM_NO_PERMISSION = "command.unclaim.no_permission";
    case UNCLAIM_SUCCESS = "command.unclaim.success";
    case UNCLAIM_FAILED = "command.unclaim.failed";
    case UNCLAIM_NOT_CLAIMED = "command.unclaim.not_claimed";
    case UNCLAIM_WRONG_FACTION = "command.unclaim.wrong_faction";

    // --- Economy / Bank ---
    case ECONOMY_DISABLED = "economy.disabled";
    case BANK_DEPOSIT_POSITIVE = "economy.bank.deposit_positive";
    case BANK_DEPOSIT_NOT_ENOUGH = "economy.bank.deposit_not_enough";
    case BANK_TRANSACTION_FAILED = "economy.bank.transaction_failed";
    case BANK_WITHDRAW_POSITIVE = "economy.bank.withdraw_positive";
    case BANK_WITHDRAW_NOT_ENOUGH = "economy.bank.withdraw_not_enough";
    case BANK_DEPOSIT_SUCCESS = "economy.bank.deposit_success";
    case BANK_WITHDRAW_SUCCESS = "economy.bank.withdraw_success";
    case BANK_BALANCE = "economy.bank.balance";
    case BANK_NO_PERMISSION_WITHDRAW = "economy.bank.no_permission_withdraw";

    // --- Claim Protection ---
    case CLAIM_INTERACT_BREAK = "claim.protection.break";
    case CLAIM_INTERACT_PLACE = "claim.protection.place";
    case CLAIM_INTERACT_USE = "claim.protection.use";

    // --- PvP Protection ---
    case PVP_FACTION_MEMBER = "pvp.protection.faction_member";
    case PVP_ALLY_MEMBER = "pvp.protection.ally_member";

    // --- Leave Command ---
    case LEAVE_NOT_IN_FACTION = "command.leave.not_in_faction";
    case LEAVE_NO_PERMISSION = "command.leave.no_permission";
    case LEAVE_LEADER_CANNOT_LEAVE = "command.leave.leader_cannot_leave";
    case LEAVE_SUCCESS = "command.leave.success";
    case LEAVE_FAILED = "command.leave.failed";

    // --- Kick Command ---
    case KICK_NOT_IN_FACTION = "command.kick.not_in_faction";
    case KICK_NO_PERMISSION = "command.kick.no_permission";
    case KICK_TARGET_NOT_FOUND = "command.kick.target_not_found";
    case KICK_TARGET_NOT_IN_FACTION = "command.kick.target_not_in_faction";
    case KICK_CANNOT_KICK_HIGHER = "command.kick.cannot_kick_higher";
    case KICK_SUCCESS = "command.kick.success";
    case KICK_TARGET_MESSAGE = "command.kick.target_message";
    case KICK_FAILED = "command.kick.failed";

    // --- Promote Command ---
    case PROMOTE_NOT_IN_FACTION = "command.promote.not_in_faction";
    case PROMOTE_NO_PERMISSION = "command.promote.no_permission";
    case PROMOTE_TARGET_NOT_FOUND = "command.promote.target_not_found";
    case PROMOTE_CANNOT_PROMOTE_SELF = "command.promote.cannot_promote_self";
    case PROMOTE_ALREADY_HIGHEST = "command.promote.already_highest";
    case PROMOTE_SUCCESS = "command.promote.success";
    case PROMOTE_TARGET_MESSAGE = "command.promote.target_message";
    case PROMOTE_FAILED = "command.promote.failed";

    // --- Home Command ---
    case HOME_NOT_IN_FACTION = "command.home.not_in_faction";
    case HOME_NOT_SET = "command.home.not_set";
    case HOME_TELEPORTED = "command.home.teleported";

    // --- SetHome Command ---
    case SETHOME_NOT_IN_FACTION = "command.sethome.not_in_faction";
    case SETHOME_NO_PERMISSION = "command.sethome.no_permission";
    case SETHOME_SUCCESS = "command.sethome.success";

    // --- DelHome Command ---
    case DELHOME_NOT_IN_FACTION = "command.delhome.not_in_faction";
    case DELHOME_NO_PERMISSION = "command.delhome.no_permission";
    case DELHOME_SUCCESS = "command.delhome.success";

    // --- Demote Command ---
    case DEMOTE_NOT_IN_FACTION = "command.demote.not_in_faction";
    case DEMOTE_NO_PERMISSION = "command.demote.no_permission";
    case DEMOTE_TARGET_NOT_FOUND = "command.demote.target_not_found";
    case DEMOTE_CANNOT_DEMOTE_SELF = "command.demote.cannot_demote_self";
    case DEMOTE_ALREADY_LOWEST = "command.demote.already_lowest";
    case DEMOTE_SUCCESS = "command.demote.success";
    case DEMOTE_TARGET_MESSAGE = "command.demote.target_message";
    case DEMOTE_FAILED = "command.demote.failed";

    // --- Leader Command ---
    case LEADER_NOT_LEADER = "command.leader.not_leader";
    case LEADER_CANNOT_TRANSFER_SELF = "command.leader.cannot_transfer_self";
    case LEADER_SUCCESS_SENDER = "command.leader.success_sender";
    case LEADER_SUCCESS_RECEIVER = "command.leader.success_receiver";
    case LEADER_FAILED = "command.leader.failed";

    // --- Chat Commands ---
    case CHAT_MODE_PUBLIC = "chat.mode.public";
    case CHAT_MODE_FACTION = "chat.mode.faction";
    case CHAT_MODE_ALLY = "chat.mode.ally";
    case CHAT_INVALID_MODE = "chat.mode.invalid";

    // --- Chat Formats ---
    case CHAT_FORMAT_FACTION = "chat.format.faction";
    case CHAT_FORMAT_ALLY = "chat.format.ally";
    case CHAT_CONSOLE_LOG = "chat.console.log";

    // --- Map Command ---
    case MAP_HEADER = "command.map.header";
    case MAP_WORLD_NOT_ALLOWED = "map.world_not_allowed";
    case MAP_LEGEND_NORTH = "map.legend.north";
    case MAP_LEGEND_EAST = "map.legend.east";
    case MAP_LEGEND_WEST = "map.legend.west";
    case MAP_LEGEND_SOUTH = "map.legend.south";

    // --- Chunk Command ---
    case CHUNK_VISUALIZE_ON = "chunk.visualize.on";
    case CHUNK_VISUALIZE_OFF = "chunk.visualize.off";

    // --- Cache Admin ---
    case CACHE_USAGE = "command.cache.usage";
    case CACHE_STATS_PANEL = "command.cache.stats_panel";

    // --- Admin Power ---
    case ADMIN_POWER_FACTION_NOT_FOUND = "command.admin.power.faction_not_found";
    case ADMIN_POWER_INVALID_AMOUNT = "command.admin.power.invalid_amount";
    case ADMIN_POWER_SET_SUCCESS = "command.admin.power.set_success";
    case ADMIN_POWER_ADD_SUCCESS = "command.admin.power.add_success";
    case ADMIN_POWER_REMOVE_SUCCESS = "command.admin.power.remove_success";

    // --- Power Freeze & Raidability ---
    case FACTION_POWER_FROZEN = "faction.power.frozen";
    case FACTION_POWER_UNFROZEN = "faction.power.unfrozen";
    case FACTION_POWER_DEATH_LOSS = "faction.power.death_loss";
    case FACTION_POWER_KILL_GAIN = "faction.power.kill_gain";

    // --- Overclaim ---
    case CLAIM_OVERCLAIM_SUCCESS = "command.claim.overclaim_success";
    case CLAIM_OVERCLAIM_NOT_RAIDABLE = "command.claim.overclaim_not_raidable";

    // --- Home Warmup ---
    case HOME_WARMUP_START = "command.home.warmup_start";
    case HOME_WARMUP_CANCELLED = "command.home.warmup_cancelled";

    // --- Admin Freeze ---
    case ADMIN_FREEZE_SUCCESS = "command.admin.freeze.success";
    case ADMIN_UNFREEZE_SUCCESS = "command.admin.unfreeze.success";

    // --- Faction Status ---
    case STATUS_RAIDABLE = "status.raidable";
    case STATUS_FROZEN = "status.frozen";
    case STATUS_NORMAL = "status.normal";

    // --- Permissions System ---
    case PERM_NO_PERMISSION = "perm.no_permission";
    case PERM_TOGGLED = "perm.toggled";
    case PERM_USAGE = "command.perm.usage";
    case PERM_LIST_HEADER = "command.perm.list_header";

    // --- Subclaim System ---
    case SUBCLAIM_DENIED = "claim.protection.subclaim_denied";
    case SUBCLAIM_CREATED = "command.subclaim.created";
    case SUBCLAIM_REMOVED = "command.subclaim.removed";
    case SUBCLAIM_NOT_FOUND = "command.subclaim.not_found";
    case SUBCLAIM_ALREADY_EXISTS = "command.subclaim.already_exists";
    case SUBCLAIM_LIST_HEADER = "command.subclaim.list_header";
    case SUBCLAIM_LIST_EMPTY = "command.subclaim.list_empty";
    case SUBCLAIM_LIST_ENTRY = "command.subclaim.list_entry";
    case SUBCLAIM_USAGE = "command.subclaim.usage";
    case SUBCLAIM_MUST_BE_IN_CLAIM = "command.subclaim.must_be_in_claim";
    case SUBCLAIM_OUTSIDE_TERRITORY = "command.subclaim.outside_territory";
    case SUBCLAIM_OVERLAPS = "command.subclaim.overlaps";
    case SUBCLAIM_ROLE_UPDATED = "command.subclaim.role_updated";
    case SUBCLAIM_INFO_HEADER = "command.subclaim.info_header";
    case SUBCLAIM_INFO_DETAILS = "command.subclaim.info_details";

    // --- Audit System ---
    case AUDIT_HEADER = "command.audit.header";
    case AUDIT_EMPTY = "command.audit.empty";
    case AUDIT_ENTRY = "command.audit.entry";
}
