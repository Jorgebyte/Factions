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

enum Role: string
{
    case LEADER = 'leader';
    case COLEADER = 'coleader';
    case MEMBER = 'member';

    public function getValue(): int
    {
        return match ($this) {
            self::LEADER => 3,
            self::COLEADER => 2,
            self::MEMBER => 1,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->getValue() > $other->getValue();
    }

    public function isAtLeast(self $other): bool
    {
        return $this->getValue() >= $other->getValue();
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }
}
