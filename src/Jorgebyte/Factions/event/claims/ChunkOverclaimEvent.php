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

namespace Jorgebyte\Factions\event\claims;

use Jorgebyte\Factions\entities\Faction;
use pocketmine\player\Player;

final class ChunkOverclaimEvent extends ChunkClaimEvent
{
    public function __construct(
        Faction $claimingFaction,
        int $chunkX,
        int $chunkZ,
        string $worldName,
        Player $claimer,
        private readonly Faction $currentOwner,
    ) {
        parent::__construct($claimingFaction, $chunkX, $chunkZ, $worldName, $claimer->getXuid(), $claimer->getName());
    }

    public function getCurrentOwner(): Faction
    {
        return $this->currentOwner;
    }
}
