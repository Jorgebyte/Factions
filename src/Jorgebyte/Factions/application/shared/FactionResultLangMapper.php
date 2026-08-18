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

namespace Jorgebyte\Factions\application\shared;

use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionResult;

final class FactionResultLangMapper
{
    private function __construct()
    {
    }

    public static function toLangKey(
        FactionResult $result,
        LangKeys $fallback,
        ?LangKeys $actionCancelledKey = null,
    ): LangKeys {
        if ($result === FactionResult::ACTION_CANCELLED) {
            return $actionCancelledKey ?? LangKeys::ACTION_CANCELLED_BY_EVENT;
        }

        return $fallback;
    }

    public static function forCreate(FactionResult $result): LangKeys
    {
        return match ($result) {
            FactionResult::ALREADY_EXISTS => LangKeys::CREATE_NAME_TAKEN,
            FactionResult::ACTION_CANCELLED => LangKeys::ACTION_CANCELLED_BY_EVENT,
            default => LangKeys::CREATE_CANCELLED,
        };
    }

    public static function forDeposit(FactionResult $result): LangKeys
    {
        return match ($result) {
            FactionResult::INVALID_AMOUNT => LangKeys::BANK_DEPOSIT_POSITIVE,
            default => LangKeys::BANK_TRANSACTION_FAILED,
        };
    }

    public static function forWithdraw(FactionResult $result): LangKeys
    {
        return match ($result) {
            FactionResult::INVALID_AMOUNT => LangKeys::BANK_WITHDRAW_POSITIVE,
            FactionResult::INSUFFICIENT_FACTION_FUNDS => LangKeys::BANK_WITHDRAW_NOT_ENOUGH,
            default => LangKeys::BANK_TRANSACTION_FAILED,
        };
    }
}
