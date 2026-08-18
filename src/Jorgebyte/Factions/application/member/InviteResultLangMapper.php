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

namespace Jorgebyte\Factions\application\member;

use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\invite\InviteResult;

final class InviteResultLangMapper
{
    private function __construct()
    {
    }

    public static function forInvite(InviteResult $result): LangKeys
    {
        return $result === InviteResult::SUCCESS
            ? LangKeys::INVITE_SUCCESS_SENDER
            : LangKeys::INVITE_CANCELLED;
    }
}
