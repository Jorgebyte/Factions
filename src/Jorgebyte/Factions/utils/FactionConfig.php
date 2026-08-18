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

namespace Jorgebyte\Factions\utils;

use Jorgebyte\Factions\Main;
use pocketmine\utils\Config;

final readonly class FactionConfig
{
    private Config $config;

    public function __construct(Main $plugin)
    {
        $this->config = new Config($plugin->getDataFolder() . "config.yml", Config::YAML);
    }

    public function getMaxAlliancesPerFaction(): int
    {
        return (int) ($this->config->getNested("alliances.max-per-faction", 1));
    }

    public function getAllyRequestTimeout(): int
    {
        return $this->config->getNested("alliances.request-timeout-seconds", 60);
    }

    public function getMinNameLength(): int
    {
        return (int) ($this->config->getNested("factions.name.min-length", 3));
    }

    public function getMaxNameLength(): int
    {
        return (int) ($this->config->getNested("factions.name.max-length", 16));
    }

    public function getNameRegex(): string
    {
        return (string) ($this->config->getNested("factions.name.regex", "/^[a-zA-Z0-9_]+$/"));
    }

    public function isNameCheckEnabled(): bool
    {
        return (bool) ($this->config->getNested("factions.name.enable-validation", true));
    }

    public function getInviteRequestTimeout(): int
    {
        return $this->config->getNested("invite.request-timeout-seconds", 60);
    }

    public function isAllyFriendlyFireEnabled(): bool
    {
        return (bool) ($this->config->getNested("alliances.ally-friendly-fire", false));
    }

    public function isMemberFriendlyFireEnabled(): bool
    {
        return (bool) ($this->config->getNested("alliances.member-friendly-fire", false));
    }

    /**
     * @return list<string>
     */
    public function getPvpBypassWorlds(): array
    {
        $worlds = $this->config->getNested("pvp.bypass-worlds", []);
        if (!is_array($worlds)) {
            return [];
        }

        $result = [];
        foreach ($worlds as $worldName) {
            if (!is_string($worldName)) {
                continue;
            }

            $normalized = strtolower(trim($worldName));
            if ($normalized === '') {
                continue;
            }

            $result[] = $normalized;
        }

        return $result;
    }

    /**
     * @return array<string, true>
     */
    public function getPvpBypassWorldLookup(): array
    {
        $lookup = [];
        foreach ($this->getPvpBypassWorlds() as $worldName) {
            $lookup[$worldName] = true;
        }

        return $lookup;
    }

    public function getClaimCost(): float
    {
        return (float) ($this->config->getNested("claims.cost-per-chunk", 1000.0));
    }

    public function getMaxClaimsPerFaction(): int
    {
        return (int) ($this->config->getNested("claims.max-per-faction", 20));
    }

    public function getAllowedClaimWorlds(): array
    {
        return $this->config->getNested("claims.allowed-worlds", []);
    }

    public function isSpawnProtectionEnabled(): bool
    {
        return (bool) ($this->config->getNested("claims.spawn-protection.enabled", false));
    }

    public function getSpawnProtectionRadius(): int
    {
        return max(0, (int) ($this->config->getNested("claims.spawn-protection.radius", $this->config->getNested("claims.spawn-protection-radius", 5))));
    }

    public function isClaimDesyncMitigationEnabled(): bool
    {
        return (bool) ($this->config->getNested("claims.desync-mitigation.enabled", true));
    }

    public function getClaimDesyncMitigationCooldownSeconds(): float
    {
        return max(0.0, (float) ($this->config->getNested("claims.desync-mitigation.cooldown-seconds", 0.35)));
    }

    public function getChunkVisualizerDensity(): string
    {
        $density = strtolower((string) ($this->config->getNested("claims.chunk-visualizer.density", "medium")));
        return in_array($density, ["low", "medium", "high"], true) ? $density : "medium";
    }

    public function getChunkVisualizerRadius(): int
    {
        return max(1, min(4, (int) ($this->config->getNested("claims.chunk-visualizer.radius", 1))));
    }

    public function getChunkVisualizerBaseIntervalTicks(): int
    {
        return max(1, (int) ($this->config->getNested("claims.chunk-visualizer.base-interval-ticks", 10)));
    }

    public function isChunkVisualizerAutoscaleEnabled(): bool
    {
        return (bool) ($this->config->getNested("claims.chunk-visualizer.autoscale.enabled", true));
    }

    public function getChunkVisualizerAutoscalePlayersStep(): int
    {
        return max(1, (int) ($this->config->getNested("claims.chunk-visualizer.autoscale.players-step", 8)));
    }

    public function getChunkVisualizerAutoscaleMaxMultiplier(): int
    {
        return max(1, (int) ($this->config->getNested("claims.chunk-visualizer.autoscale.max-interval-multiplier", 4)));
    }

    public function getMaxMembersPerFaction(): int
    {
        return (int) ($this->config->getNested("factions.members.max-per-faction", 15));
    }

    public function isWarmupEnabled(): bool
    {
        return (bool) ($this->config->getNested("cache.warmup.enabled", true));
    }

    public function isWarmupPrioritizeOnlineEnabled(): bool
    {
        return (bool) ($this->config->getNested("cache.warmup.prioritize-online", true));
    }

    public function getWarmupBatchSize(): int
    {
        return max(1, (int) ($this->config->getNested("cache.warmup.batch-size", 25)));
    }

    public function getWarmupIntervalTicks(): int
    {
        return max(1, (int) ($this->config->getNested("cache.warmup.interval-ticks", 10)));
    }

    public function getFactionCacheMaxEntries(): int
    {
        return max(1, (int) ($this->config->getNested("cache.faction.max-entries", 1000)));
    }

    public function getFactionCacheMemoryThresholdMb(): int
    {
        return max(8, (int) ($this->config->getNested("cache.faction.memory-threshold-mb", 50)));
    }

    public function getFactionCacheLowTtlSeconds(): int
    {
        return max(60, (int) ($this->config->getNested("cache.faction.ttl-seconds.low", 1800)));
    }

    public function getFactionCacheMediumTtlSeconds(): int
    {
        return max(60, (int) ($this->config->getNested("cache.faction.ttl-seconds.medium", 3600)));
    }

    public function getFactionCacheHighTtlSeconds(): int
    {
        return max(60, (int) ($this->config->getNested("cache.faction.ttl-seconds.high", 300)));
    }

    public function getInviteCacheTtlOverrideSeconds(): int
    {
        return max(0, (int) ($this->config->getNested("cache.invite.ttl-override-seconds", 0)));
    }

    public function getInitialPower(): int
    {
        return max(0, (int) ($this->config->getNested("power.initial-power", 2)));
    }

    public function getDisbandCooldownSeconds(): int
    {
        return max(0, (int) ($this->config->getNested("factions.creation.disband-cooldown-seconds", 86400)));
    }

    public function getPowerPerMember(): int
    {
        return max(1, (int) ($this->config->getNested("power.power-per-member", 10)));
    }

    public function getPowerPerKill(): int
    {
        return max(0, (int) ($this->config->getNested("power.power-per-kill", 5)));
    }

    public function getPowerPerDeath(): int
    {
        return max(0, (int) ($this->config->getNested("power.power-per-death", 5)));
    }

    public function getPowerFreezeSeconds(): int
    {
        return max(10, (int) ($this->config->getNested("power.power-freeze-seconds", 600)));
    }

    public function getPowerRegenIntervalMinutes(): int
    {
        return max(1, (int) ($this->config->getNested("power.regen-interval-minutes", 5)));
    }

    public function getPowerRegenAmount(): int
    {
        return max(1, (int) ($this->config->getNested("power.regen-amount", 1)));
    }

    public function getHomeTeleportWarmupSeconds(): int
    {
        return max(0, (int) ($this->config->getNested("home.teleport-warmup-seconds", 5)));
    }
}
