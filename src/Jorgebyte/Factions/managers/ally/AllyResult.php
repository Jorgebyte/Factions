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

enum AllyResult
{
    case SUCCESS;
    case INVALID_REQUEST;
    case SELF_TARGET;
    case NO_PERMISSION;
    case ALREADY_ALLIED;
    case ALREADY_PENDING;
    case MAX_ALLIANCES_REACHED;
    case REQUEST_NOT_FOUND;
    case REQUEST_EXPIRED;
    case ACTION_CANCELLED;
    case FACTION_NOT_FOUND;
    case INTERNAL_ERROR;
}
