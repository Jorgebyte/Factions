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

use Jorgebyte\Factions\application\chat\FactionChatService;
use Jorgebyte\Factions\managers\session\SessionManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

final readonly class ChatListener implements Listener
{
    public function __construct(
        private SessionManager $sessionManager,
        private FactionChatService $chatService,
    ) {
    }

    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $result = $this->chatService->dispatch($player, $event->getMessage());

        if ($result->handled) {
            $event->cancel();
            return;
        }

        if ($result->resetToPublic) {
            $this->sessionManager->resetChatContext($player->getXuid());
            $event->uncancel();
        }
    }
}
