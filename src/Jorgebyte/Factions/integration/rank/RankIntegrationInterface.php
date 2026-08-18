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

interface RankIntegrationInterface
{
    public function isAvailable(): bool;

    public function getChatPrefix(Player $player): string;

    public function getChatIdentity(Player $player): string;

    public function getNameTagPrefix(Player $player): string;

    public function getDisplayName(Player $player): string;
}
