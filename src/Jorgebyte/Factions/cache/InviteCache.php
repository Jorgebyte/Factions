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

final class InviteCache
{
    /** @var array<string, InviteCacheEntry> */
    private array $invites = [];

    private CacheMetrics $metrics;

    private CachePolicyService $policy;

    public function __construct(?CachePolicyService $policy = null)
    {
        $this->policy = $policy ?? CachePolicyService::defaults();
        $this->metrics = new CacheMetrics();
    }

    public function add(string $playerXuid, int $factionId): void
    {
        $this->invites[$playerXuid] = new InviteCacheEntry($factionId, time());
    }

    public function get(string $playerXuid): ?InviteCacheEntry
    {
        $entry = $this->invites[$playerXuid] ?? null;
        if ($entry === null) {
            $this->metrics->recordMiss();
            return null;
        }

        $this->metrics->recordHit();
        return $entry;
    }

    public function remove(string $playerXuid): void
    {
        unset($this->invites[$playerXuid]);
    }

    public function getCount(): int
    {
        return count($this->invites);
    }

    public function cleanExpired(int $timeout): void
    {
        $now = time();
        $evicted = 0;
        foreach ($this->invites as $xuid => $entry) {
            if ($this->isExpired($entry, $timeout, $now)) {
                unset($this->invites[$xuid]);
                $evicted++;
            }
        }

        $this->metrics->recordEvictions($evicted);
    }

    public function isExpired(InviteCacheEntry $entry, int $fallbackTimeout, ?int $now = null): bool
    {
        $timeout = $this->policy->resolveInviteTtl($fallbackTimeout);
        $current = $now ?? time();
        return ($current - $entry->timestamp) > $timeout;
    }

    public function getMetrics(): CacheMetrics
    {
        return $this->metrics;
    }
}
