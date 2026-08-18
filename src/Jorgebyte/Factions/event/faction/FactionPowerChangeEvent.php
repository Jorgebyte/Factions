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

use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\event\FactionEvent;

final class FactionPowerChangeEvent extends FactionEvent
{
    public function __construct(
        Faction $faction,
        private readonly int $oldPower,
        private int $newPower,
    ) {
        parent::__construct($faction);
    }

    public function getOldPower(): int
    {
        return $this->oldPower;
    }

    public function getNewPower(): int
    {
        return $this->newPower;
    }

    public function setNewPower(int $power): void
    {
        $this->newPower = $power;
    }
}
