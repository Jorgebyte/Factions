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

namespace Jorgebyte\Factions\Tests\Unit;

use Jorgebyte\Factions\cache\CachePriority;
use PHPUnit\Framework\TestCase;

final class CachePriorityTest extends TestCase
{
    public function testCachePriorityValues(): void
    {
        $this->assertSame(1, CachePriority::LOW->value);
        $this->assertSame(2, CachePriority::MEDIUM->value);
        $this->assertSame(3, CachePriority::HIGH->value);
        $this->assertSame(4, CachePriority::CRITICAL->value);
    }

    public function testCachePriorityOrdering(): void
    {
        $this->assertSame(
            [1, 2, 3, 4],
            array_map(static fn (CachePriority $priority): int => $priority->value, CachePriority::cases())
        );
    }
}
