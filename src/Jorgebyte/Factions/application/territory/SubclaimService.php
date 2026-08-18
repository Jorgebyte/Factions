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

use Generator;
use Jorgebyte\Factions\application\audit\FactionAuditService;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\Subclaim;
use Jorgebyte\Factions\event\territory\FactionSubclaimCreateEvent;
use Jorgebyte\Factions\event\territory\FactionSubclaimRemoveEvent;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use pocketmine\player\Player;
use pocketmine\world\Position;
use poggit\libasynql\DataConnector;
use Throwable;

final readonly class SubclaimService
{
    public const RESULT_SUCCESS = "SUCCESS";
    public const RESULT_ALREADY_EXISTS = "ALREADY_EXISTS";
    public const RESULT_OVERLAPS = "OVERLAPS";
    public const RESULT_OUTSIDE_TERRITORY = "OUTSIDE_TERRITORY";
    public const RESULT_ERROR = "ERROR";

    public function __construct(
        private DataConnector $connector,
        private ?ClaimManager $claimManager = null,
        private ?FactionAuditService $auditService = null
    ) {
    }

    /**
     * Create a new subclaim for a faction around a position or region.
     * @return Generator<mixed, mixed, mixed, string> returns one of RESULT_* constants
     */
    public function createSubclaim(
        Faction $faction,
        Player $creator,
        string $name,
        Position $center,
        int $radius,
        Role $minRole
    ): Generator {
        $nameLower = strtolower($name);
        if ($faction->getSubclaim($nameLower) !== null) {
            return self::RESULT_ALREADY_EXISTS;
        }

        $worldName = $center->getWorld()->getFolderName();
        $minX = $center->getFloorX() - $radius;
        $maxX = $center->getFloorX() + $radius;
        $minY = max($center->getWorld()->getMinY(), $center->getFloorY() - $radius);
        $maxY = min($center->getWorld()->getMaxY(), $center->getFloorY() + $radius);
        $minZ = $center->getFloorZ() - $radius;
        $maxZ = $center->getFloorZ() + $radius;

        $subclaim = new Subclaim(
            0,
            $faction->id,
            $name,
            $worldName,
            $minX,
            $minY,
            $minZ,
            $maxX,
            $maxY,
            $maxZ,
            $minRole
        );

        // Overlap check with existing subclaims
        foreach ($faction->getSubclaims() as $existingSubclaim) {
            if ($subclaim->intersects($existingSubclaim)) {
                return self::RESULT_OVERLAPS;
            }
        }

        // Territory check: verify that all 4 corners are inside claims owned by the faction
        if ($this->claimManager !== null) {
            $corners = [
                [$minX, $minZ],
                [$minX, $maxZ],
                [$maxX, $minZ],
                [$maxX, $maxZ],
            ];
            foreach ($corners as [$cornerX, $cornerZ]) {
                $chunkX = $cornerX >> 4;
                $chunkZ = $cornerZ >> 4;
                $claim = $this->claimManager->getClaim($chunkX, $chunkZ, $worldName);
                if ($claim === null || $claim->factionId !== $faction->id) {
                    return self::RESULT_OUTSIDE_TERRITORY;
                }
            }
        }

        $event = new FactionSubclaimCreateEvent($faction, $subclaim, $creator);
        $event->call();
        if ($event->isCancelled()) {
            return self::RESULT_ERROR;
        }

        try {
            yield from $this->connector->asyncInsert("subclaims.insert", [
                "faction_id" => $faction->id,
                "name" => $name,
                "world_name" => $worldName,
                "min_x" => $minX,
                "min_y" => $minY,
                "min_z" => $minZ,
                "max_x" => $maxX,
                "max_y" => $maxY,
                "max_z" => $maxZ,
                "min_role" => $minRole->value,
            ]);
        } catch (Throwable) {
            return self::RESULT_ERROR;
        }

        $faction->addSubclaim($subclaim);

        if ($this->auditService !== null) {
            yield from $this->auditService->log(
                $faction,
                $creator->getXuid(),
                $creator->getName(),
                "SUBCLAIM_CREATE",
                "Created subclaim '{$name}' for role '{$minRole->value}'"
            );
        }

        return self::RESULT_SUCCESS;
    }

    /**
     * Update the minimum required role for a subclaim.
     * @return Generator<mixed, mixed, mixed, bool>
     */
    public function updateSubclaimRole(Faction $faction, Player $updater, string $name, Role $newRole): Generator
    {
        $subclaim = $faction->getSubclaim($name);
        if ($subclaim === null) {
            return false;
        }

        try {
            yield from $this->connector->asyncChange("subclaims.update_role", [
                "faction_id" => $faction->id,
                "name" => $subclaim->name,
                "min_role" => $newRole->value,
            ]);
        } catch (Throwable) {
            return false;
        }

        $updatedSubclaim = $subclaim->withMinRole($newRole);
        $faction->addSubclaim($updatedSubclaim);

        if ($this->auditService !== null) {
            yield from $this->auditService->log(
                $faction,
                $updater->getXuid(),
                $updater->getName(),
                "SUBCLAIM_UPDATE_ROLE",
                "Updated subclaim '{$subclaim->name}' role to '{$newRole->value}'"
            );
        }

        return true;
    }

    /**
     * Remove an existing subclaim by name.
     * @return Generator<mixed, mixed, mixed, bool>
     */
    public function removeSubclaim(Faction $faction, Player $remover, string $name): Generator
    {
        $subclaim = $faction->getSubclaim($name);
        if ($subclaim === null) {
            return false;
        }

        $event = new FactionSubclaimRemoveEvent($faction, $subclaim, $remover);
        $event->call();
        if ($event->isCancelled()) {
            return false;
        }

        try {
            yield from $this->connector->asyncChange("subclaims.delete", [
                "faction_id" => $faction->id,
                "name" => $subclaim->name,
            ]);
        } catch (Throwable) {
            return false;
        }

        $faction->removeSubclaim($name);

        if ($this->auditService !== null) {
            yield from $this->auditService->log(
                $faction,
                $remover->getXuid(),
                $remover->getName(),
                "SUBCLAIM_REMOVE",
                "Removed subclaim '{$subclaim->name}'"
            );
        }

        return true;
    }
}
