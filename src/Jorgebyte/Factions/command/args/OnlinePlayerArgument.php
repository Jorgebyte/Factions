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

namespace Jorgebyte\Factions\command\args;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

final class OnlinePlayerArgument extends StringEnumArgument
{
    public function getTypeName(): string
    {
        return 'online_player';
    }

    public function canParse(string $testString, CommandSender $sender): bool
    {
        return $this->getValue($testString) instanceof Player;
    }

    public function parse(string $argument, CommandSender $sender): ?Player
    {
        return $this->getValue($argument);
    }

    public function getValue(string $string): ?Player
    {
        return Server::getInstance()->getPlayerByPrefix($string);
    }

    public function getEnumValues(): array
    {
        return array_map(
            static fn (Player $player): string => $player->getName(),
            Server::getInstance()->getOnlinePlayers(),
        );
    }
}
