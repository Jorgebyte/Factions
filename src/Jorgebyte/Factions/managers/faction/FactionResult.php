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

enum FactionResult
{
    case SUCCESS;
    case NOT_FOUND;
    case ALREADY_EXISTS;
    case FACTION_FULL;
    case INVALID_REQUEST;
    case INVALID_STATE;
    case INVALID_AMOUNT;
    case INSUFFICIENT_FACTION_FUNDS;
    case ACTION_CANCELLED;
    case INTERNAL_ERROR;
}
