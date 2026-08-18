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

namespace Jorgebyte\Factions\event\faction;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

final class FactionCreateEvent extends Event implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        private readonly Player $creator,
        private string $factionName,
    ) {
    }

    public function getCreator(): Player
    {
        return $this->creator;
    }

    public function getFactionName(): string
    {
        return $this->factionName;
    }

    public function setFactionName(string $name): void
    {
        $this->factionName = $name;
    }
}
