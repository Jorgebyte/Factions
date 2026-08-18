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

use Jorgebyte\Factions\cache\CachePriority;
use Jorgebyte\Factions\integration\display\FactionDisplaySyncService;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\utils\PlayerUtils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use SOFe\AwaitGenerator\Await;

final readonly class PlayerListener implements Listener
{
    public function __construct(
        private SessionManager $sessionManager,
        private FactionManager $factionManager,
        private FactionDisplaySyncService $displaySyncService,
    ) {
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        Await::f2c(/**
         * @throws \Throwable
         */ function () use ($event) {
            $player = $event->getPlayer();
            $xuid = $player->getXuid();
            PlayerUtils::registerPlayer($player);

            $faction = yield from $this->factionManager->loadFactionByPlayerXuid($xuid);

            $this->sessionManager->createSession($xuid);
            if ($faction !== null) {
                $member = $faction->getMember($xuid);

                if ($member !== null && $member->getPlayerName() !== $player->getName()) {
                    yield from $this->factionManager->updatePlayerName($xuid, $player->getName());
                }

                $this->factionManager->setFactionCachePriority($faction->id, CachePriority::CRITICAL);
            }

            $this->displaySyncService->syncPlayerState($player);
        });
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $xuid = $player->getXuid();
        PlayerUtils::unregisterByXuid($xuid);
        $session = $this->sessionManager->getSession($xuid);

        if ($session !== null) {
            $faction = $this->factionManager->getPlayerFaction($xuid);
            if ($faction !== null) {
                $this->factionManager->setFactionCachePriority($faction->id, CachePriority::MEDIUM);
            }
            $this->sessionManager->closeSession($xuid);
        }
    }
}
