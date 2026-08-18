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

use Jorgebyte\Factions\entities\AuditLogEntry;
use Jorgebyte\Factions\entities\Claim;
use Jorgebyte\Factions\entities\Faction;

final class FactionCacheManager
{
    private FactionMemoryCache $factionCache;

    private ClaimMemoryCache $claimCache;

    /** @var array<string, int> playerXuid -> factionId */
    private array $playerFactionMap = [];

    /** @var array<int, array<int, AuditLogEntry>> factionId -> list of recent logs */
    private array $auditShortCache = [];

    public function __construct(?CachePolicyService $policy = null)
    {
        $policy = $policy ?? CachePolicyService::defaults();
        $this->factionCache = new FactionMemoryCache($policy);
        $this->claimCache = new ClaimMemoryCache($policy);
    }

    public function getFaction(int $id): ?Faction
    {
        return $this->factionCache->get($id);
    }

    public function cacheFaction(Faction $faction): void
    {
        $this->factionCache->set($faction, CachePriority::HIGH);
        foreach ($faction->getMembers() as $member) {
            $this->playerFactionMap[$member->playerXuid] = $faction->id;
        }
    }

    public function removeFaction(int $id): void
    {
        $faction = $this->factionCache->get($id);
        if ($faction !== null) {
            foreach ($faction->getMembers() as $member) {
                unset($this->playerFactionMap[$member->playerXuid]);
            }
        }
        $this->factionCache->remove($id);
        unset($this->auditShortCache[$id]);
    }

    public function getPlayerFactionId(string $xuid): ?int
    {
        return $this->playerFactionMap[$xuid] ?? null;
    }

    public function setPlayerFactionMapping(string $xuid, int $factionId): void
    {
        $this->playerFactionMap[$xuid] = $factionId;
    }

    public function clearPlayerFactionMapping(string $xuid): void
    {
        unset($this->playerFactionMap[$xuid]);
    }

    public function getClaim(string $worldName, int $chunkX, int $chunkZ): ?Claim
    {
        return $this->claimCache->get($worldName, $chunkX, $chunkZ);
    }

    public function cacheClaim(Claim $claim): void
    {
        $this->claimCache->set($claim);
    }

    public function removeClaim(string $worldName, int $chunkX, int $chunkZ): void
    {
        $this->claimCache->remove($worldName, $chunkX, $chunkZ);
    }

    public function purgeFactionClaims(int $factionId): void
    {
        $this->claimCache->removeFactionClaims($factionId);
    }

    public function getLoadedFactions(): array
    {
        return $this->factionCache->getAllLoadedFactions();
    }

    public function getLoadedClaims(): array
    {
        return $this->claimCache->getAllClaims();
    }

    public function getMetrics(): CacheMetrics
    {
        return $this->factionCache->getMetrics();
    }

    public function clean(): void
    {
        $this->factionCache->clean();
        $this->claimCache->clean();
    }
}
