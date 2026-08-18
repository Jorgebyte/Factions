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

namespace Jorgebyte\Factions\application\territory;

use Generator;
use Jorgebyte\Factions\application\shared\CommandResult;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\claim\ClaimRequest;
use Jorgebyte\Factions\managers\claim\UnclaimRequest;
use pocketmine\player\Player;

final readonly class TerritoryActionService
{
    public function __construct(
        private ClaimManager $claimManager,
    ) {
    }

    /** @return Generator<mixed, mixed, mixed, CommandResult> */
    public function claimChunk(Player $player, Faction $faction): Generator
    {
        $position = $player->getPosition();
        $world = $player->getWorld();
        $spawn = $world->getSafeSpawn();

        $request = new ClaimRequest(
            $position->getFloorX() >> 4,
            $position->getFloorZ() >> 4,
            $world->getFolderName(),
            $spawn->getFloorX() >> 4,
            $spawn->getFloorZ() >> 4,
            $player->getXuid(),
            $player->getName(),
        );

        $result = yield from $this->claimManager->claimChunk($request, $faction);

        $messageKey = ClaimResultLangMapper::forClaim($result->result);

        return new CommandResult($messageKey);
    }

    /** @return Generator<mixed, mixed, mixed, CommandResult> */
    public function unclaimChunk(Player $player, Faction $faction): Generator
    {
        $position = $player->getPosition();
        $request = new UnclaimRequest(
            $position->getFloorX() >> 4,
            $position->getFloorZ() >> 4,
            $player->getWorld()->getFolderName(),
        );

        $result = yield from $this->claimManager->unclaimChunk($request, $faction);

        $messageKey = ClaimResultLangMapper::forUnclaim($result->result);

        return new CommandResult($messageKey);
    }
}
