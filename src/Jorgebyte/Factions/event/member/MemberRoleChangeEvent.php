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

use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\event\FactionMemberEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

final class MemberRoleChangeEvent extends FactionMemberEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Faction $faction,
        Member $member,
        private readonly Role $oldRole,
        private Role $newRole,
    ) {
        parent::__construct($faction, $member);
    }

    public function getOldRole(): Role
    {
        return $this->oldRole;
    }

    public function getNewRole(): Role
    {
        return $this->newRole;
    }

    public function setNewRole(Role $role): void
    {
        $this->newRole = $role;
    }
}
