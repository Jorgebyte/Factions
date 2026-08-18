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

use Jorgebyte\Factions\application\shared\CommandResult;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Faction;

final readonly class AllyPrecheckResult
{
    private function __construct(
        public bool $isSuccess,
        public ?Faction $myFaction,
        public ?Faction $targetFaction,
        public ?Role $memberRole,
        public ?CommandResult $error,
    ) {
    }

    public static function success(Faction $myFaction, Faction $targetFaction, Role $memberRole): self
    {
        return new self(true, $myFaction, $targetFaction, $memberRole, null);
    }

    public static function fail(CommandResult $error): self
    {
        return new self(false, null, null, null, $error);
    }
}
