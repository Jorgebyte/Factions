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

namespace Jorgebyte\Factions\event;

use Jorgebyte\Factions\entities\Faction;
use pocketmine\event\Event;

abstract class FactionEvent extends Event
{
    public function __construct(protected Faction $faction)
    {
    }

    public function getFaction(): Faction
    {
        return $this->faction;
    }
}
