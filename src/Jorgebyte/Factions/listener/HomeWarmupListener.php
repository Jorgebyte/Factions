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

use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\Main;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\Position;

final class HomeWarmupListener implements Listener
{
    private static ?self $instance = null;

    /** @var array<string, array{handler: TaskHandler<mixed>, startPos: Position, targetPos: Position}> */
    private array $warmups = [];

    public function __construct(private readonly Main $plugin)
    {
        self::$instance = $this;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public function startWarmup(Player $player, Position $targetPos, int $warmupSeconds): void
    {
        $xuid = $player->getXuid();
        $this->cancelWarmup($player, false);

        $player->sendMessage(Lang::t($player, LangKeys::HOME_WARMUP_START->value, [
            "time" => (string) $warmupSeconds,
        ]));

        $handler = $this->plugin->getScheduler()->scheduleDelayedTask(
            new class ($player, $targetPos, $this, $xuid) extends Task {
                public function __construct(
                    private readonly Player $player,
                    private readonly Position $targetPos,
                    private readonly HomeWarmupListener $listener,
                    private readonly string $xuid
                ) {
                }

                public function onRun(): void
                {
                    $this->listener->removeWarmup($this->xuid);
                    if ($this->player->isOnline()) {
                        $this->player->teleport($this->targetPos);
                        $this->player->sendMessage(Lang::t($this->player, LangKeys::HOME_TELEPORTED->value));
                    }
                }
            },
            20 * $warmupSeconds
        );

        $this->warmups[$xuid] = [
            'handler' => $handler,
            'startPos' => $player->getPosition(),
            'targetPos' => $targetPos,
        ];
    }

    public function cancelWarmup(Player $player, bool $sendMessage = true): void
    {
        $xuid = $player->getXuid();
        if (isset($this->warmups[$xuid])) {
            $this->warmups[$xuid]['handler']->cancel();
            unset($this->warmups[$xuid]);
            if ($sendMessage && $player->isOnline()) {
                $player->sendMessage(Lang::t($player, LangKeys::HOME_WARMUP_CANCELLED->value));
            }
        }
    }

    public function removeWarmup(string $xuid): void
    {
        unset($this->warmups[$xuid]);
    }

    /**
     * @priority MONITOR
     * @ignoreCanceled true
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $this->cancelWarmup($entity);
        }
    }

    /**
     * @priority MONITOR
     * @ignoreCanceled true
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $xuid = $player->getXuid();
        if (!isset($this->warmups[$xuid])) {
            return;
        }

        $from = $event->getFrom();
        $to = $event->getTo();

        if ($from->getFloorX() !== $to->getFloorX() || $from->getFloorY() !== $to->getFloorY() || $from->getFloorZ() !== $to->getFloorZ()) {
            $this->cancelWarmup($player);
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $this->cancelWarmup($event->getPlayer(), false);
    }
}
