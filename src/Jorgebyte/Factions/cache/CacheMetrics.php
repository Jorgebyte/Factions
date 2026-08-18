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

namespace Jorgebyte\Factions\cache;

final class CacheMetrics
{
    private int $hits = 0;

    private int $misses = 0;

    private int $evictions = 0;

    public function recordHit(): void
    {
        $this->hits++;
    }

    public function recordMiss(): void
    {
        $this->misses++;
    }

    public function recordEvictions(int $count = 1): void
    {
        if ($count > 0) {
            $this->evictions += $count;
        }
    }

    public function hits(): int
    {
        return $this->hits;
    }

    public function misses(): int
    {
        return $this->misses;
    }

    public function evictions(): int
    {
        return $this->evictions;
    }

    /** @return array{hits:int, misses:int, evictions:int} */
    public function toArray(): array
    {
        return [
            'hits' => $this->hits,
            'misses' => $this->misses,
            'evictions' => $this->evictions,
        ];
    }
}
