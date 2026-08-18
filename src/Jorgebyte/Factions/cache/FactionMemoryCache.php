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

use Jorgebyte\Factions\entities\Faction;

final class FactionMemoryCache
{
    /** @var array<int, FactionCacheEntry> */
    private array $entries = [];

    private CacheMetrics $metrics;

    private CachePolicyService $policy;

    public function __construct(?CachePolicyService $policy = null)
    {
        $this->policy = $policy ?? CachePolicyService::defaults();
        $this->metrics = new CacheMetrics();
    }

    public function get(int $id): ?Faction
    {
        if (!isset($this->entries[$id])) {
            $this->metrics->recordMiss();
            return null;
        }

        $entry = $this->entries[$id];
        $entry->lastAccess = time();
        $this->metrics->recordHit();
        return $entry->faction;
    }

    public function set(Faction $faction, CachePriority $priority = CachePriority::MEDIUM): void
    {
        $this->entries[$faction->id] = new FactionCacheEntry($faction, time(), $priority);
    }

    public function remove(int $id): void
    {
        unset($this->entries[$id]);
    }

    /**
     * @return Faction[]
     */
    public function getAllLoadedFactions(): array
    {
        $factions = [];
        foreach ($this->entries as $entry) {
            $factions[] = $entry->faction;
        }
        return $factions;
    }

    public function getCount(): int
    {
        return count($this->entries);
    }

    public function getEntry(int $id): ?FactionCacheEntry
    {
        return $this->entries[$id] ?? null;
    }

    public function clean(): void
    {
        $currentTime = time();
        $cleanedCount = 0;
        foreach ($this->entries as $id => $entry) {
            if ($entry->priority === CachePriority::CRITICAL) {
                continue;
            }

            $ttl = $this->policy->factionTtl($entry->priority);

            if (($currentTime - $entry->lastAccess) > $ttl) {
                $this->remove($id);
                $cleanedCount++;
            }
        }

        $this->metrics->recordEvictions($cleanedCount);
    }

    public function evictLeastRecentlyUsed(int $count = 100): void
    {
        $removableEntries = array_filter($this->entries, fn ($entry) => $entry->priority !== CachePriority::CRITICAL);

        // Sort by lastAccess ASC (oldest first)
        uasort($removableEntries, function ($a, $b) {
            /* @var FactionCacheEntry $a */
            /* @var FactionCacheEntry $b */
            // Low priority should be evicted first, then by time
            if ($a->priority !== $b->priority) {
                return $a->priority->value <=> $b->priority->value;
            }
            return $a->lastAccess <=> $b->lastAccess;
        });

        $toEvict = array_slice($removableEntries, 0, $count, true);
        foreach (array_keys($toEvict) as $id) {
            $this->remove($id);
        }

        $this->metrics->recordEvictions(count($toEvict));
    }

    public function shouldEvict(): bool
    {
        return $this->policy->shouldEvictFactionCache(count($this->entries), memory_get_usage(true));
    }

    public function getMetrics(): CacheMetrics
    {
        return $this->metrics;
    }
}
