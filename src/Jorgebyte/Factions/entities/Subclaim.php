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
use pocketmine\world\Position;

final readonly class Subclaim
{
    public function __construct(
        public int $id,
        public int $factionId,
        public string $name,
        public string $worldName,
        public int $minX,
        public int $minY,
        public int $minZ,
        public int $maxX,
        public int $maxY,
        public int $maxZ,
        public Role $minRole = Role::COLEADER,
    ) {
    }

    public function contains(Position $position): bool
    {
        if (strtolower($position->getWorld()->getFolderName()) !== strtolower($this->worldName)) {
            return false;
        }

        $x = $position->getFloorX();
        $y = $position->getFloorY();
        $z = $position->getFloorZ();

        return $x >= $this->minX && $x <= $this->maxX &&
               $y >= $this->minY && $y <= $this->maxY &&
               $z >= $this->minZ && $z <= $this->maxZ;
    }

    public function intersects(self $other): bool
    {
        if (strtolower($this->worldName) !== strtolower($other->worldName)) {
            return false;
        }

        return $this->minX <= $other->maxX && $this->maxX >= $other->minX &&
               $this->minY <= $other->maxY && $this->maxY >= $other->minY &&
               $this->minZ <= $other->maxZ && $this->maxZ >= $other->minZ;
    }

    public function withMinRole(Role $minRole): self
    {
        return new self(
            $this->id,
            $this->factionId,
            $this->name,
            $this->worldName,
            $this->minX,
            $this->minY,
            $this->minZ,
            $this->maxX,
            $this->maxY,
            $this->maxZ,
            $minRole
        );
    }

    public function isRoleAllowed(Role $role): bool
    {
        return $role->isAtLeast($this->minRole);
    }
}
