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

final class AllyMemoryCache
{
    /** @var array<string, AllyCacheEntry> */
    private array $alliances = [];

    /** @var array<int, array<int, true>> */
    private array $byFaction = [];

    private bool $hydrated = false;

    public function areAllied(int $id1, int $id2): bool
    {
        $entry = $this->alliances[$this->getKey($id1, $id2)] ?? null;
        return $entry !== null && $entry->accepted;
    }

    public function add(int $id1, int $id2): void
    {
        $this->alliances[$this->getKey($id1, $id2)] = new AllyCacheEntry($id1, $id2, true, time());
        $this->byFaction[$id1][$id2] = true;
        $this->byFaction[$id2][$id1] = true;
    }

    public function remove(int $id1, int $id2): void
    {
        unset($this->alliances[$this->getKey($id1, $id2)]);
        unset($this->byFaction[$id1][$id2], $this->byFaction[$id2][$id1]);

        if (isset($this->byFaction[$id1]) && $this->byFaction[$id1] === []) {
            unset($this->byFaction[$id1]);
        }
        if (isset($this->byFaction[$id2]) && $this->byFaction[$id2] === []) {
            unset($this->byFaction[$id2]);
        }
    }

    /** @return int[] */
    public function getAlliedFactionIds(int $factionId): array
    {
        return array_map('intval', array_keys($this->byFaction[$factionId] ?? []));
    }

    public function countAlliesForFaction(int $factionId): int
    {
        return count($this->byFaction[$factionId] ?? []);
    }

    public function markHydrated(): void
    {
        $this->hydrated = true;
    }

    public function isHydrated(): bool
    {
        return $this->hydrated;
    }

    public function clear(): void
    {
        $this->alliances = [];
        $this->byFaction = [];
        $this->hydrated = false;
    }

    private function getKey(int $id1, int $id2): string
    {
        return min($id1, $id2) . "-" . max($id1, $id2);
    }
}
