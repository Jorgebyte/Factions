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
use Jorgebyte\Factions\api\FactionsAPI;
use pocketmine\command\CommandSender;

final class FactionMemberNameArgument extends StringEnumArgument
{
    public function getTypeName(): string
    {
        return 'member';
    }

    public function canParse(string $testString, CommandSender $sender): bool
    {
        return $testString !== '';
    }

    public function parse(string $argument, CommandSender $sender): string
    {
        return $argument;
    }

    public function getEnumValues(): array
    {
        try {
            $manager = FactionsAPI::getInstance()->getFactionManager();
        } catch (\Throwable) {
            return [];
        }

        $values = [];
        foreach ($manager->getLoadedFactions() as $faction) {
            foreach ($faction->getMembers() as $member) {
                $values[] = $member->getPlayerName();
            }
        }

        return $values;
    }
}
