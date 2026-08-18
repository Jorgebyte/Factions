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

use Jorgebyte\Factions\entities\enums\FactionPermission;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use pocketmine\block\Block;
use pocketmine\block\Button;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Lever;
use pocketmine\block\tile\Container as ContainerTile;
use pocketmine\block\Trapdoor;
use pocketmine\inventory\InventoryHolder;
use pocketmine\player\Player;
use pocketmine\world\Position;

final readonly class ClaimAccessService
{
    public function __construct(
        private ClaimManager $claimManager,
        private FactionManager $factionManager,
    ) {
    }

    public function isPositionAllowed(Position $position, ?int $playerFactionId): bool
    {
        return $this->isChunkAllowed(
            $position->getFloorX() >> 4,
            $position->getFloorZ() >> 4,
            $position->getWorld()->getFolderName(),
            $playerFactionId,
        );
    }

    public function isChunkAllowed(int $chunkX, int $chunkZ, string $worldName, ?int $playerFactionId): bool
    {
        $claim = $this->claimManager->getClaim($chunkX, $chunkZ, $worldName);
        if ($claim === null) {
            return true;
        }

        if ($playerFactionId !== null && $playerFactionId === $claim->factionId) {
            return true;
        }

        // Allow access if owner faction is raidable (power <= 0, frozen, or underpowered)
        $ownerFaction = $this->factionManager->getLoadedFactionById($claim->factionId);
        if ($ownerFaction !== null && $ownerFaction->isRaidable()) {
            return true;
        }

        return false;
    }

    public function isPositionAllowedForExplosion(Position $position): bool
    {
        $chunkX = $position->getFloorX() >> 4;
        $chunkZ = $position->getFloorZ() >> 4;
        $worldName = $position->getWorld()->getFolderName();

        $claim = $this->claimManager->getClaim($chunkX, $chunkZ, $worldName);
        if ($claim === null) {
            return true;
        }

        $ownerFaction = $this->factionManager->getLoadedFactionById($claim->factionId);
        if ($ownerFaction !== null && $ownerFaction->isRaidable()) {
            return true;
        }

        return false;
    }

    public function canPlayerAccessPosition(Position $position, Player $player): bool
    {
        $chunkX = $position->getFloorX() >> 4;
        $chunkZ = $position->getFloorZ() >> 4;
        $worldName = $position->getWorld()->getFolderName();

        $claim = $this->claimManager->getClaim($chunkX, $chunkZ, $worldName);
        if ($claim === null) {
            return true;
        }

        $ownerFaction = $this->factionManager->getLoadedFactionById($claim->factionId);
        if ($ownerFaction !== null && $ownerFaction->isRaidable()) {
            return true;
        }

        $playerFactionId = $this->factionManager->getPlayerFactionId($player->getXuid());
        if ($playerFactionId === null || $playerFactionId !== $claim->factionId) {
            return false;
        }

        if ($ownerFaction === null) {
            return true;
        }

        // check subclaim role restriction if inside subclaim
        $subclaim = $ownerFaction->getSubclaimAt($position);
        if ($subclaim !== null) {
            $member = $ownerFaction->getMember($player->getXuid());
            if ($member === null || !$subclaim->isRoleAllowed($member->getRole())) {
                return false;
            }
        }

        return true;
    }

    public function canPlayerPerformPermission(
        Position $position,
        Player $player,
        FactionPermission $permission
    ): bool {
        $chunkX = $position->getFloorX() >> 4;
        $chunkZ = $position->getFloorZ() >> 4;
        $worldName = $position->getWorld()->getFolderName();

        $claim = $this->claimManager->getClaim($chunkX, $chunkZ, $worldName);
        if ($claim === null) {
            return true;
        }

        $ownerFaction = $this->factionManager->getLoadedFactionById($claim->factionId);
        if ($ownerFaction === null || $ownerFaction->isRaidable()) {
            return true;
        }

        $playerFactionId = $this->factionManager->getPlayerFactionId($player->getXuid());
        if ($playerFactionId === null || $playerFactionId !== $claim->factionId) {
            return false;
        }

        $member = $ownerFaction->getMember($player->getXuid());
        if ($member === null) {
            return false;
        }

        // check subclaim role restriction if inside subclaim
        $subclaim = $ownerFaction->getSubclaimAt($position);
        if ($subclaim !== null && !$subclaim->isRoleAllowed($member->getRole())) {
            return false;
        }

        return $ownerFaction->permissions->hasPermission($member->getRole(), $permission);
    }

    public function isProtectedInteractBlock(Block $block): bool
    {
        return $block instanceof InventoryHolder ||
            $block->getPosition()->getWorld()->getTile($block->getPosition()) instanceof ContainerTile ||
            $block instanceof Door ||
            $block instanceof Trapdoor ||
            $block instanceof FenceGate ||
            $block instanceof Button ||
            $block instanceof Lever;
    }
}
