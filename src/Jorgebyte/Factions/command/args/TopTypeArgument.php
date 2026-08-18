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

final class TopTypeArgument extends StringEnumArgument
{
    /** @var string[] */
    private const TYPES = ['power', 'kills', 'money'];

    public function getTypeName(): string
    {
        return 'top_type';
    }

    public function canParse(string $testString, CommandSender $sender): bool
    {
        return in_array(strtolower($testString), self::TYPES, true);
    }

    public function parse(string $argument, CommandSender $sender): string
    {
        return strtolower($argument);
    }

    public function getEnumValues(): array
    {
        return self::TYPES;
    }
}
