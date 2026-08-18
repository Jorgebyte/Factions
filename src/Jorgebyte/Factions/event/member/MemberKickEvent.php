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
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\event\FactionMemberEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

final class MemberKickEvent extends FactionMemberEvent implements Cancellable
{
    use CancellableTrait;

    private readonly string $kickerXuid;

    private readonly string $kickerName;

    public function __construct(
        Faction $faction,
        Member $member,
        Player|string $kicker,
        ?string $kickerName = null,
    ) {
        parent::__construct($faction, $member);

        if ($kicker instanceof Player) {
            $this->kickerXuid = $kicker->getXuid();
            $this->kickerName = $kicker->getName();
            return;
        }

        $this->kickerXuid = $kicker;
        $this->kickerName = $kickerName ?? '';
    }

    public function getKickerXuid(): string
    {
        return $this->kickerXuid;
    }

    public function getKickerName(): string
    {
        return $this->kickerName;
    }
}
