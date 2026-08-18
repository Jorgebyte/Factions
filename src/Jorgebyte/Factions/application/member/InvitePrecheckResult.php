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

use Jorgebyte\Factions\application\shared\CommandResult;
use Jorgebyte\Factions\entities\Faction;

final readonly class InvitePrecheckResult
{
    private function __construct(
        public bool $isSuccess,
        public ?Faction $faction,
        public ?CommandResult $error,
    ) {
    }

    public static function success(Faction $faction): self
    {
        return new self(true, $faction, null);
    }

    public static function fail(CommandResult $error): self
    {
        return new self(false, null, $error);
    }
}
