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

use Jorgebyte\Factions\utils\FactionConfig;
use pocketmine\player\Player;

final class ClaimDesyncMitigationService
{
    /** @var array<string, float> */
    private array $lastMitigationAt = [];

    public function __construct(
        private readonly FactionConfig $factionConfig,
    ) {
    }

    public function mitigate(Player $player): void
    {
        if (!$this->factionConfig->isClaimDesyncMitigationEnabled()) {
            return;
        }

        $xuid = $player->getXuid();
        $now = microtime(true);
        $cooldown = $this->factionConfig->getClaimDesyncMitigationCooldownSeconds();

        if (($this->lastMitigationAt[$xuid] ?? 0.0) + $cooldown > $now) {
            return;
        }

        $this->lastMitigationAt[$xuid] = $now;
        $player->teleport($player->getLocation());
    }
}
