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

namespace Jorgebyte\Factions\command\subcommands\territory;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\ally\AllyManager;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class MapCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly ClaimManager $claimManager,
        private readonly AllyManager $allyManager,
    ) {
        parent::__construct("map", "View nearby claims");
        $this->setPermission(Permissions::FACTIONS_COMMAND_MAP->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_MAP->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $worldName = $sender->getWorld()->getFolderName();

        if (!$this->claimManager->isWorldAllowed($worldName)) {
            $sender->sendMessage(Lang::t($sender, LangKeys::MAP_WORLD_NOT_ALLOWED->value));
            return;
        }

        $sender->sendMessage(Lang::t($sender, LangKeys::MAP_HEADER->value));

        $chunkX = $sender->getPosition()->getFloorX() >> 4;
        $chunkZ = $sender->getPosition()->getFloorZ() >> 4;
        $radius = 4; // 9x9 map

        $myFaction = $this->factionManager->getPlayerFaction($sender->getXuid());

        $mapLines = [];
        // Header with directions
        $mapLines[] = Lang::t($sender, LangKeys::MAP_LEGEND_NORTH->value);
        $mapLines[] = Lang::t($sender, LangKeys::MAP_LEGEND_EAST->value) . "  " . Lang::t($sender, LangKeys::MAP_LEGEND_WEST->value) . "  " . Lang::t($sender, LangKeys::MAP_LEGEND_SOUTH->value);

        for ($dz = -$radius; $dz <= $radius; $dz++) {
            $line = "";
            for ($dx = -$radius; $dx <= $radius; $dx++) {
                $cx = $chunkX + $dx;
                $cz = $chunkZ + $dz;

                $claim = $this->claimManager->getClaim($cx, $cz, $worldName);

                $line .= match (true) {
                    $dx === 0 && $dz === 0 => TextFormat::AQUA . "+", // Player position
                    $claim === null => TextFormat::GRAY . "-", // Wilderness
                    $myFaction !== null && $claim->factionId === $myFaction->id => TextFormat::GREEN . "+", // Own faction
                    $myFaction !== null && $this->allyManager->areAllied($myFaction->id, $claim->factionId) => TextFormat::LIGHT_PURPLE . "+", // Ally
                    default => TextFormat::RED . "#" // Enemy/Neutral
                };
            }
            $mapLines[] = $line;
        }

        foreach ($mapLines as $l) {
            $sender->sendMessage($l);
        }
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
