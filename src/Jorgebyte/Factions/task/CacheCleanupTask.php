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

namespace Jorgebyte\Factions\task;

use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\invite\InviteManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class CacheCleanupTask extends Task
{
    private int $runs = 0;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly ClaimManager $claimManager,
        private readonly InviteManager $inviteManager,
    ) {
    }

    public function onRun(): void
    {
        $this->runs++;
        $cache = $this->factionManager->getFactionCache();

        if ($cache->shouldEvict()) {
            $cache->evictLeastRecentlyUsed();
        }
        $cache->clean();

        $claimCache = $this->claimManager->getClaimCache();
        if ($claimCache->shouldEvict()) {
            $claimCache->evictLeastRecentlyUsed();
        }
        $claimCache->clean();

        $this->inviteManager->cleanExpiredInvites();

        if ($this->runs % 5 === 0) {
            $factionMetrics = $this->factionManager->getFactionCacheMetrics()->toArray();
            $inviteMetrics = $this->inviteManager->getCacheMetrics()->toArray();
            Server::getInstance()->getLogger()->debug(
                'Factions cache metrics | faction=' . json_encode($factionMetrics) . ' invite=' . json_encode($inviteMetrics),
            );
        }
    }
}
