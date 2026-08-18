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

namespace Jorgebyte\Factions\application\territory;

use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\claim\ClaimResult;

final class ClaimResultLangMapper
{
    private function __construct()
    {
    }

    public static function forClaim(ClaimResult $result): LangKeys
    {
        return match ($result) {
            ClaimResult::SUCCESS => LangKeys::CLAIM_SUCCESS,
            ClaimResult::OVERCLAIM_SUCCESS => LangKeys::CLAIM_OVERCLAIM_SUCCESS,
            ClaimResult::WORLD_NOT_ALLOWED => LangKeys::CLAIM_WORLD_NOT_ALLOWED,
            ClaimResult::TOO_CLOSE_TO_SPAWN => LangKeys::CLAIM_TOO_CLOSE_TO_SPAWN,
            ClaimResult::ALREADY_CLAIMED => LangKeys::CLAIM_ALREADY_CLAIMED,
            ClaimResult::NOT_ENOUGH_MONEY => LangKeys::CLAIM_NOT_ENOUGH_MONEY,
            ClaimResult::MAX_LIMIT_REACHED => LangKeys::CLAIM_MAX_LIMIT_REACHED,
            ClaimResult::ACTION_CANCELLED => LangKeys::ACTION_CANCELLED_BY_EVENT,
            default => LangKeys::CLAIM_FAILED,
        };
    }

    public static function forUnclaim(ClaimResult $result): LangKeys
    {
        return match ($result) {
            ClaimResult::SUCCESS => LangKeys::UNCLAIM_SUCCESS,
            ClaimResult::UNCLAIM_NOT_CLAIMED => LangKeys::UNCLAIM_NOT_CLAIMED,
            ClaimResult::UNCLAIM_WRONG_FACTION => LangKeys::UNCLAIM_WRONG_FACTION,
            default => LangKeys::UNCLAIM_FAILED,
        };
    }
}
