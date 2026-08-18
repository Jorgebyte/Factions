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

namespace Jorgebyte\Factions\event\member;

use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\event\FactionEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

final class MemberJoinEvent extends FactionEvent implements Cancellable
{
    use CancellableTrait;

    private readonly string $playerXuid;

    private readonly string $playerName;

    public function __construct(
        Faction $faction,
        Player|string $player,
        ?string $playerName = null,
    ) {
        parent::__construct($faction);

        if ($player instanceof Player) {
            $this->playerXuid = $player->getXuid();
            $this->playerName = $player->getName();
            return;
        }

        $this->playerXuid = $player;
        $this->playerName = $playerName ?? '';
    }

    public function getPlayerXuid(): string
    {
        return $this->playerXuid;
    }

    public function getPlayerName(): string
    {
        return $this->playerName;
    }
}
