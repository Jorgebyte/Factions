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

final readonly class CachePolicyService
{
    /** @param array<int, int> $factionTtls */
    public function __construct(
        private int $factionMaxEntries = 1000,
        private int $factionMemoryThresholdBytes = 50 * 1024 * 1024,
        private array $factionTtls = [],
        private int $inviteTtlOverrideSeconds = 0,
    ) {
    }

    /**
     * @param array<string, int> $ttls
     */
    public static function fromConfig(
        int $factionMaxEntries,
        int $factionMemoryThresholdMb,
        array $ttls,
        int $inviteTtlOverrideSeconds,
    ): self {
        return new self(
            max(1, $factionMaxEntries),
            max(8, $factionMemoryThresholdMb) * 1024 * 1024,
            [
                CachePriority::LOW->value => max(60, (int) ($ttls['low'] ?? 1800)),
                CachePriority::MEDIUM->value => max(60, (int) ($ttls['medium'] ?? 3600)),
                CachePriority::HIGH->value => max(60, (int) ($ttls['high'] ?? 300)),
                CachePriority::CRITICAL->value => PHP_INT_MAX,
            ],
            max(0, $inviteTtlOverrideSeconds),
        );
    }

    public static function defaults(): self
    {
        return self::fromConfig(1000, 50, [
            'low' => 1800,
            'medium' => 3600,
            'high' => 300,
        ], 0);
    }

    public function factionTtl(CachePriority $priority): int
    {
        return $this->factionTtls[$priority->value] ?? 3600;
    }

    public function shouldEvictFactionCache(int $entryCount, int $memoryUsageBytes): bool
    {
        return $entryCount > $this->factionMaxEntries || $memoryUsageBytes > $this->factionMemoryThresholdBytes;
    }

    public function resolveInviteTtl(int $fallback): int
    {
        return $this->inviteTtlOverrideSeconds > 0 ? $this->inviteTtlOverrideSeconds : max(1, $fallback);
    }
}
