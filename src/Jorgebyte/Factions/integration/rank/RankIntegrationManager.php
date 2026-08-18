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

use pocketmine\player\Player;
use pocketmine\Server;

final class RankIntegrationManager
{
    private RankIntegrationInterface $integration;

    public function __construct()
    {
        $this->integration = $this->resolveIntegration();
    }

    public function refresh(): void
    {
        $this->integration = $this->resolveIntegration();
    }

    public function isAvailable(): bool
    {
        return $this->integration->isAvailable();
    }

    public function getChatPrefix(Player $player): string
    {
        return $this->integration->getChatPrefix($player);
    }

    public function getChatIdentity(Player $player): string
    {
        return $this->integration->getChatIdentity($player);
    }

    public function getNameTagPrefix(Player $player): string
    {
        return $this->integration->getNameTagPrefix($player);
    }

    public function getDisplayName(Player $player): string
    {
        return $this->integration->getDisplayName($player);
    }

    private function resolveIntegration(): RankIntegrationInterface
    {
        $rankSystem = Server::getInstance()->getPluginManager()->getPlugin('RankSystem');
        if ($rankSystem !== null && $rankSystem->isEnabled()) {
            $integration = new RankSystemIntegration();
            if ($integration->isAvailable()) {
                return $integration;
            }
        }

        return new NullRankIntegration();
    }
}
