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

namespace Jorgebyte\Factions\entities;

use Jorgebyte\Factions\entities\enums\Role;

final class Member
{
    public function __construct(
        public readonly int $factionId,
        public readonly string $playerXuid,
        public string $playerName,
        public Role $role,
    ) {
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }

    public function setPlayerName(string $name): void
    {
        $this->playerName = $name;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
