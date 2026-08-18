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

namespace Jorgebyte\Factions\integration\rank;

use IvanCraft623\RankSystem\rank\Rank;
use IvanCraft623\RankSystem\session\SessionManager as RankSessionManager;
use pocketmine\player\Player;
use pocketmine\Server;
use Throwable;

final class RankSystemIntegration implements RankIntegrationInterface
{
    public function isAvailable(): bool
    {
        $plugin = Server::getInstance()->getPluginManager()->getPlugin('RankSystem');
        return $plugin !== null && $plugin->isEnabled();
    }

    public function getChatPrefix(Player $player): string
    {
        $format = $this->resolveChatFormat($player);

        return (string) ($format['prefix'] ?? '');
    }

    public function getChatIdentity(Player $player): string
    {
        $chatFormat = $this->resolveChatFormat($player);

        $prefix = (string) ($chatFormat['prefix'] ?? '');
        $nameColor = (string) ($chatFormat['nameColor'] ?? '');

        return $prefix . $nameColor . $player->getName();
    }

    public function getNameTagPrefix(Player $player): string
    {
        $format = $this->resolveNameTagFormat($player);

        return (string) ($format['prefix'] ?? '');
    }

    public function getDisplayName(Player $player): string
    {
        $format = $this->resolveNameTagFormat($player);

        $nameColor = (string) ($format['nameColor'] ?? '');
        return $this->getNameTagPrefix($player) . $nameColor . $player->getName();
    }

    /** @return array<string, mixed> */
    private function resolveChatFormat(Player $player): array
    {
        return $this->resolveRank($player)?->getChatFormat() ?? [];
    }

    /** @return array<string, mixed> */
    private function resolveNameTagFormat(Player $player): array
    {
        return $this->resolveRank($player)?->getNameTagFormat() ?? [];
    }

    /** @return Rank|null */
    private function resolveRank(Player $player): ?object
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $session = RankSessionManager::getInstance()->get($player);
            return $session?->getHighestRank();
        } catch (Throwable) {
            return null;
        }
    }
}
