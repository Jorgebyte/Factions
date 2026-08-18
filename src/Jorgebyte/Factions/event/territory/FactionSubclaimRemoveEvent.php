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

namespace Jorgebyte\Factions\event\territory;

use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\Subclaim;
use Jorgebyte\Factions\event\FactionEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

final class FactionSubclaimRemoveEvent extends FactionEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Faction $faction,
        private readonly Subclaim $subclaim,
        private readonly Player $remover
    ) {
        parent::__construct($faction);
    }

    public function getSubclaim(): Subclaim
    {
        return $this->subclaim;
    }

    public function getRemover(): Player
    {
        return $this->remover;
    }
}
