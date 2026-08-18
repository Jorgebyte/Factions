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

enum ClaimResult
{
    case SUCCESS;
    case OVERCLAIM_SUCCESS;
    case WORLD_NOT_ALLOWED;
    case TOO_CLOSE_TO_SPAWN;
    case ALREADY_CLAIMED;
    case UNCLAIM_NOT_CLAIMED;
    case UNCLAIM_WRONG_FACTION;
    case NOT_ENOUGH_MONEY;
    case MAX_LIMIT_REACHED;
    case ACTION_CANCELLED;
    case INTERNAL_ERROR;
}
