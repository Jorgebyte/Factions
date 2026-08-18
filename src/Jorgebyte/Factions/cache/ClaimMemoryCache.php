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

use Jorgebyte\Factions\entities\Claim;

final class ClaimMemoryCache
{
    /** @var array<string, ClaimCacheEntry> */
    private array $claims = [];

    private CachePolicyService $policy;

    public function __construct(?CachePolicyService $policy = null)
    {
        $this->policy = $policy ?? CachePolicyService::defaults();
    }

    public function get(string $worldName, int $chunkX, int $chunkZ): ?Claim
    {
        $key = $this->buildKey($worldName, $chunkX, $chunkZ);
        if (!isset($this->claims[$key])) {
            return null;
        }

        $this->claims[$key]->touch();
        return $this->claims[$key]->claim;
    }

    public function set(Claim $claim, CachePriority $priority = CachePriority::MEDIUM): void
    {
        $key = $this->buildKey($claim->worldName, $claim->chunkX, $claim->chunkZ);
        $now = time();
        $this->claims[$key] = new ClaimCacheEntry($claim, $priority, $now, $now);
    }

    public function remove(string $worldName, int $chunkX, int $chunkZ): void
    {
        unset($this->claims[$this->buildKey($worldName, $chunkX, $chunkZ)]);
    }

    public function clear(): void
    {
        $this->claims = [];
    }

    public function getCount(): int
    {
        return count($this->claims);
    }

    public function clean(): void
    {
        $now = time();
        $removed = 0;

        foreach ($this->claims as $key => $entry) {
            if ($entry->priority === CachePriority::CRITICAL) {
                continue;
            }

            if (($now - $entry->lastAccess) > $this->policy->factionTtl($entry->priority)) {
                unset($this->claims[$key]);
                $removed++;
            }
        }

        // Intentionally no metrics object to keep claim cache lightweight
    }

    public function shouldEvict(): bool
    {
        return $this->policy->shouldEvictFactionCache(count($this->claims), memory_get_usage(true));
    }

    public function evictLeastRecentlyUsed(int $count = 100): void
    {
        $removable = array_filter(
            $this->claims,
            static fn (ClaimCacheEntry $entry): bool => $entry->priority !== CachePriority::CRITICAL
        );

        uasort($removable, static function (ClaimCacheEntry $a, ClaimCacheEntry $b): int {
            if ($a->priority !== $b->priority) {
                return $a->priority->value <=> $b->priority->value;
            }

            return $a->lastAccess <=> $b->lastAccess;
        });

        foreach (array_slice(array_keys($removable), 0, $count) as $key) {
            unset($this->claims[$key]);
        }
    }

    public function removeByFactionId(int $factionId): void
    {
        foreach ($this->claims as $key => $entry) {
            if ($entry->claim->factionId === $factionId) {
                unset($this->claims[$key]);
            }
        }
    }

    public function removeFactionClaims(int $factionId): void
    {
        $this->removeByFactionId($factionId);
    }

    public function getAllClaims(): array
    {
        $claims = [];
        foreach ($this->claims as $entry) {
            $claims[] = $entry->claim;
        }
        return $claims;
    }

    private function buildKey(string $worldName, int $chunkX, int $chunkZ): string
    {
        return strtolower($worldName) . ':' . $chunkX . ':' . $chunkZ;
    }
}
