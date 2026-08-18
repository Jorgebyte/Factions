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

namespace Jorgebyte\Factions\application\relation;

use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\ally\AllyResult;

final class AllyResultLangMapper
{
    private function __construct()
    {
    }

    public static function forAccept(AllyResult $result): LangKeys
    {
        return match ($result) {
            AllyResult::NO_PERMISSION => LangKeys::RELATION_NO_PERMISSION,
            AllyResult::REQUEST_NOT_FOUND, AllyResult::REQUEST_EXPIRED => LangKeys::ACCEPT_NO_INVITE,
            AllyResult::ACTION_CANCELLED => LangKeys::ACTION_CANCELLED_BY_EVENT,
            default => LangKeys::RELATION_UPDATE_FAILED,
        };
    }

    public static function forDeny(AllyResult $result): LangKeys
    {
        return match ($result) {
            AllyResult::REQUEST_NOT_FOUND, AllyResult::REQUEST_EXPIRED => LangKeys::DENY_NO_INVITE,
            AllyResult::ACTION_CANCELLED => LangKeys::ACTION_CANCELLED_BY_EVENT,
            default => LangKeys::RELATION_UPDATE_FAILED,
        };
    }

    public static function forRequest(AllyResult $result): LangKeys
    {
        return match ($result) {
            AllyResult::NO_PERMISSION => LangKeys::RELATION_NO_PERMISSION,
            AllyResult::MAX_ALLIANCES_REACHED => LangKeys::RELATION_LIMIT_REACHED,
            AllyResult::ALREADY_ALLIED, AllyResult::ALREADY_PENDING => LangKeys::RELATION_ALREADY_RELATION,
            AllyResult::ACTION_CANCELLED => LangKeys::ACTION_CANCELLED_BY_EVENT,
            default => LangKeys::RELATION_UPDATE_FAILED,
        };
    }

    public static function forNeutral(AllyResult $result): LangKeys
    {
        return match ($result) {
            AllyResult::REQUEST_NOT_FOUND, AllyResult::REQUEST_EXPIRED => LangKeys::RELATION_ALREADY_RELATION,
            AllyResult::ACTION_CANCELLED => LangKeys::ACTION_CANCELLED_BY_EVENT,
            default => LangKeys::RELATION_UPDATE_FAILED,
        };
    }
}
