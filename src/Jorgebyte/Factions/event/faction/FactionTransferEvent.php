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
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\event\FactionEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

final class FactionTransferEvent extends FactionEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Faction $faction,
        private readonly Member $fromLeader,
        private Member $toNewLeader,
    ) {
        parent::__construct($faction);
    }

    public function getFromLeader(): Member
    {
        return $this->fromLeader;
    }

    public function getNewLeader(): Member
    {
        return $this->toNewLeader;
    }

    public function setNewLeader(Member $newLeader): void
    {
        $this->toNewLeader = $newLeader;
    }
}
