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

namespace Jorgebyte\Factions\entities\enums;

enum ChatMode: string
{
    case PUBLIC = 'public';
    case FACTION = 'faction';
    case ALLY = 'ally';

    public static function fromAlias(?string $input): ?self
    {
        if ($input === null) {
            return null;
        }

        return match (strtolower($input)) {
            'p', 'public', 'pub' => self::PUBLIC,
            'f', 'faction', 'fac' => self::FACTION,
            'a', 'ally', 'alliance' => self::ALLY,
            default => null,
        };
    }

    public function next(): self
    {
        return match ($this) {
            self::PUBLIC => self::FACTION,
            self::FACTION => self::ALLY,
            self::ALLY => self::PUBLIC,
        };
    }
}
