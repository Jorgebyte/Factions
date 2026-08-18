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

use Jorgebyte\Factions\entities\enums\FactionPermission;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\event\FactionEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

final class FactionPermissionChangeEvent extends FactionEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Faction $faction,
        private readonly Player $actor,
        private readonly Role $targetRole,
        private readonly FactionPermission $permission,
        private readonly bool $newValue
    ) {
        parent::__construct($faction);
    }

    public function getActor(): Player
    {
        return $this->actor;
    }

    public function getTargetRole(): Role
    {
        return $this->targetRole;
    }

    public function getPermission(): FactionPermission
    {
        return $this->permission;
    }

    public function getNewValue(): bool
    {
        return $this->newValue;
    }
}
