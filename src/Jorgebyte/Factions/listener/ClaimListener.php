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

namespace Jorgebyte\Factions\listener;

use Jorgebyte\Factions\application\territory\ClaimAccessService;
use Jorgebyte\Factions\application\territory\ClaimDesyncMitigationService;
use Jorgebyte\Factions\entities\enums\FactionPermission;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use pocketmine\block\tile\Container as ContainerTile;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockExplodeEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\InventoryHolder;
use pocketmine\player\Player;
use pocketmine\world\Position;

final class ClaimListener implements Listener
{
    private const DENY_MESSAGE_COOLDOWN = 0.75;

    /** @var array<string, float> */
    private array $lastDenyAt = [];

    public function __construct(
        private readonly ClaimAccessService $claimAccessService,
        private readonly ClaimDesyncMitigationService $mitigationService,
        private FactionManager $factionManager,
    ) {
    }

    /**
     * @param BlockBreakEvent $event
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $pos = $event->getBlock()->getPosition();
        if (!$this->claimAccessService->canPlayerPerformPermission($pos, $player, FactionPermission::BREAK)) {
            $this->denyInteraction($player, LangKeys::CLAIM_INTERACT_BREAK->value, $event);
        }
    }

    /**
     * @param BlockPlaceEvent $event
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            if (!$this->claimAccessService->canPlayerPerformPermission($block->getPosition(), $player, FactionPermission::PLACE)) {
                $this->denyInteraction($player, LangKeys::CLAIM_INTERACT_PLACE->value, $event);
                return;
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($this->claimAccessService->isProtectedInteractBlock($block)) {
            $isContainer = $block instanceof InventoryHolder || $block->getPosition()->getWorld()->getTile($block->getPosition()) instanceof ContainerTile;
            $permission = $isContainer ? FactionPermission::CONTAINERS : FactionPermission::INTERACT;

            if (!$this->claimAccessService->canPlayerPerformPermission($block->getPosition(), $player, $permission)) {
                $this->denyInteraction($player, LangKeys::CLAIM_INTERACT_USE->value, $event);
            }
        }
    }

    /**
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onBucketEmpty(PlayerBucketEmptyEvent $event): void
    {
        $player = $event->getPlayer();
        $playerFactionId = $this->getPlayerFactionId($player);

        $clickedPos = $event->getBlockClicked()->getPosition();
        if (!$this->claimAccessService->isPositionAllowed($clickedPos, $playerFactionId)) {
            $this->denyInteraction($player, LangKeys::CLAIM_INTERACT_PLACE->value, $event);
            return;
        }

        $side = $clickedPos->getSide($event->getBlockFace());
        $affectedPos = new Position($side->getFloorX(), $side->getFloorY(), $side->getFloorZ(), $clickedPos->getWorld());

        if (!$this->claimAccessService->isPositionAllowed($affectedPos, $playerFactionId)) {
            $this->denyInteraction($player, LangKeys::CLAIM_INTERACT_PLACE->value, $event);
        }
    }

    /**
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onBucketFill(PlayerBucketFillEvent $event): void
    {
        $player = $event->getPlayer();

        if (!$this->claimAccessService->isPositionAllowed($event->getBlockClicked()->getPosition(), $this->getPlayerFactionId($player))) {
            $this->denyInteraction($player, LangKeys::CLAIM_INTERACT_USE->value, $event);
        }
    }

    /**
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onEntityExplode(EntityExplodeEvent $event): void
    {
        $blockList = $event->getBlockList();
        $filtered = [];
        foreach ($blockList as $block) {
            if ($this->claimAccessService->isPositionAllowedForExplosion($block->getPosition())) {
                $filtered[] = $block;
            }
        }
        $event->setBlockList($filtered);
    }

    /**
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onBlockExplode(BlockExplodeEvent $event): void
    {
        $blockList = $event->getAffectedBlocks();
        $filtered = [];
        foreach ($blockList as $block) {
            if ($this->claimAccessService->isPositionAllowedForExplosion($block->getPosition())) {
                $filtered[] = $block;
            }
        }
        $event->setAffectedBlocks($filtered);
    }

    /**
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onEntityDamage(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        if (!$damager instanceof Player) {
            return;
        }

        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            // Player vs Player is handled in FactionListener / CombatListener
            return;
        }

        $pos = $entity->getPosition();
        if (!$this->claimAccessService->canPlayerPerformPermission($pos, $damager, FactionPermission::BREAK)) {
            $event->cancel();
        }
    }

    private function getPlayerFactionId(Player $player): ?int
    {
        return $this->factionManager->getPlayerFaction($player->getXuid())?->id;
    }

    /**
     * @param Player $player
     * @param string $messageKey
     * @param BlockBreakEvent|BlockPlaceEvent|PlayerInteractEvent|PlayerBucketEmptyEvent|PlayerBucketFillEvent $event
     */
    private function denyInteraction(Player $player, string $messageKey, BlockBreakEvent|BlockPlaceEvent|PlayerInteractEvent|PlayerBucketEmptyEvent|PlayerBucketFillEvent $event): void
    {
        $now = microtime(true);
        $xuid = $player->getXuid();
        $last = $this->lastDenyAt[$xuid] ?? 0.0;
        if (($now - $last) >= self::DENY_MESSAGE_COOLDOWN) {
            $player->sendMessage(Lang::t($player, $messageKey));
            $this->lastDenyAt[$xuid] = $now;
        }

        $event->cancel();
        $this->mitigationService->mitigate($player);
    }
}
