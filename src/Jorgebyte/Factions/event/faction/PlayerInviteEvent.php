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
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

final class PlayerInviteEvent extends FactionEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Faction $faction,
        private readonly Player $invitedBy,
        private readonly Player $invitedPlayer,
    ) {
        parent::__construct($faction);
    }

    public function getInvitedBy(): Player
    {
        return $this->invitedBy;
    }

    public function getInvitedPlayer(): Player
    {
        return $this->invitedPlayer;
    }
}
