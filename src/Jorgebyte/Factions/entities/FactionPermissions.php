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

use Jorgebyte\Factions\entities\enums\FactionPermission;
use Jorgebyte\Factions\entities\enums\Role;

final class FactionPermissions
{
    /** @var array<string, array<string, bool>> role -> permission -> granted */
    private array $matrix = [];

    public function __construct()
    {
        $this->applyDefaults();
    }

    public function applyDefaults(): void
    {
        $this->matrix = [
            Role::LEADER->value => [],
            Role::COLEADER->value => [],
            Role::MEMBER->value => [],
        ];

        foreach (FactionPermission::cases() as $perm) {
            $this->matrix[Role::LEADER->value][$perm->value] = true;
            $this->matrix[Role::COLEADER->value][$perm->value] = true;
        }

        $memberDefault = [
            FactionPermission::BREAK->value => true,
            FactionPermission::PLACE->value => true,
            FactionPermission::CONTAINERS->value => true,
            FactionPermission::INTERACT->value => true,
            FactionPermission::BANK_DEPOSIT->value => true,
            FactionPermission::BANK_WITHDRAW->value => false,
            FactionPermission::INVITE->value => false,
            FactionPermission::KICK->value => false,
            FactionPermission::PROMOTE->value => false,
            FactionPermission::DEMOTE->value => false,
            FactionPermission::SET_HOME->value => false,
            FactionPermission::CLAIM->value => false,
            FactionPermission::UNCLAIM->value => false,
            FactionPermission::SUBCLAIM->value => false,
            FactionPermission::ALLIES->value => false,
        ];

        foreach ($memberDefault as $permKey => $granted) {
            $this->matrix[Role::MEMBER->value][$permKey] = $granted;
        }
    }

    public function hasPermission(Role $role, FactionPermission $permission): bool
    {
        if ($role === Role::LEADER) {
            return true;
        }

        return $this->matrix[$role->value][$permission->value] ?? false;
    }

    public function setPermission(Role $role, FactionPermission $permission, bool $granted): void
    {
        if ($role === Role::LEADER) {
            return; 
        }

        $this->matrix[$role->value][$permission->value] = $granted;
    }

    public function togglePermission(Role $role, FactionPermission $permission): bool
    {
        if ($role === Role::LEADER) {
            return true;
        }

        $current = $this->hasPermission($role, $permission);
        $newVal = !$current;
        $this->setPermission($role, $permission, $newVal);
        return $newVal;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getMatrix(): array
    {
        return $this->matrix;
    }

    public function loadFromDb(array $rows): void
    {
        foreach ($rows as $row) {
            $roleStr = (string) $row['role'];
            $permStr = (string) $row['permission'];
            $granted = ((int) $row['granted']) === 1;

            if (isset($this->matrix[$roleStr])) {
                $this->matrix[$roleStr][$permStr] = $granted;
            }
        }
    }
}
