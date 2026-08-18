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

namespace Jorgebyte\Factions\managers\invite;

use Jorgebyte\Factions\cache\CacheMetrics;
use Jorgebyte\Factions\cache\CachePolicyService;
use Jorgebyte\Factions\cache\InviteCache;
use Jorgebyte\Factions\utils\FactionConfig;

final class InviteManager
{
    private InviteCache $cache;

    public function __construct(
        private readonly FactionConfig $config,
        CachePolicyService $cachePolicy,
    ) {
        $this->cache = new InviteCache($cachePolicy);
    }

    public function addInvite(int $factionId, string $invitedByXuid, string $invitedXuid): InviteResult
    {
        if ($factionId <= 0 || $invitedByXuid === '' || $invitedXuid === '' || $invitedByXuid === $invitedXuid) {
            return InviteResult::INVALID_REQUEST;
        }

        $entry = $this->cache->get($invitedXuid);
        if ($entry !== null) {
            if ($this->cache->isExpired($entry, $this->config->getInviteRequestTimeout())) {
                $this->cache->remove($invitedXuid);
            } else {
                return InviteResult::ALREADY_INVITED;
            }
        }

        $this->cache->add($invitedXuid, $factionId);
        return InviteResult::SUCCESS;
    }

    /**
     * @return int|null ID of faction the player is invited to
     */
    public function getInvite(string $playerXuid): ?int
    {
        if ($playerXuid === '') {
            return null;
        }

        $entry = $this->cache->get($playerXuid);
        if ($entry === null) {
            return null;
        }

        if ($this->cache->isExpired($entry, $this->config->getInviteRequestTimeout())) {
            $this->cache->remove($playerXuid);
            return null;
        }

        return $entry->factionId;
    }

    public function removeInvite(string $playerXuid): void
    {
        if ($playerXuid !== '') {
            $this->cache->remove($playerXuid);
        }
    }

    public function cleanExpiredInvites(): void
    {
        $this->cache->cleanExpired($this->config->getInviteRequestTimeout());
    }

    public function getCacheMetrics(): CacheMetrics
    {
        return $this->cache->getMetrics();
    }

    public function getCacheSize(): int
    {
        return $this->cache->getCount();
    }
}
