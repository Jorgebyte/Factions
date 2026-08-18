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

namespace Jorgebyte\Factions\event\claims;

use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\event\FactionEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

final class ChunkUnclaimEvent extends FactionEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Faction $faction,
        private readonly int $chunkX,
        private readonly int $chunkZ,
        private readonly string $worldName,
        private readonly Player $unclaimer,
    ) {
        parent::__construct($faction);
    }

    public function getChunkX(): int
    {
        return $this->chunkX;
    }

    public function getChunkZ(): int
    {
        return $this->chunkZ;
    }

    public function getWorldName(): string
    {
        return $this->worldName;
    }

    public function getUnclaimer(): Player
    {
        return $this->unclaimer;
    }
}
