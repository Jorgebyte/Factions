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
use JsonException;
use pocketmine\utils\Config;

final class ConfigDefaults
{
    /**
     * @throws JsonException
     */
    public static function applyDefaults(Main $plugin): void
    {
        $configPath = $plugin->getDataFolder() . "config.yml";
        $config = new Config($configPath, Config::YAML, []);

        $defaults = self::getDefaultConfig();

        $modified = false;

        foreach ($defaults as $key => $value) {
            if (!$config->exists($key)) {
                $config->set($key, $value);
                $modified = true;
            } else {
                if (is_array($value) && is_array($config->get($key))) {
                    $hasChanges = false;
                    $merged = self::mergeRecursive($value, $config->get($key), $hasChanges);
                    if ($hasChanges) {
                        $config->set($key, $merged);
                        $modified = true;
                    }
                }
            }
        }

        if ($modified) {
            $config->save();
        }
    }

    public static function getDefaultConfig(): array
    {
        return [
            "default-language" => "en_US",
            "economy" => [
                "provider" => "bedrockeconomy",
            ],
            "database" => [
                "type" => "sqlite",
                "sqlite" => [
                    "file" => "data.sqlite",
                ],
                "mysql" => [
                    "host" => "127.0.0.1",
                    "username" => "root",
                    "password" => "",
                    "schema" => "factions_db",
                ],
                "worker-limit" => 1,
            ],
            "claims" => [
                "cost-per-chunk" => 1000.0,
                "max-per-faction" => 20,
                "spawn-protection" => [
                    "enabled" => false,
                    "radius" => 5,
                ],
                "allowed-worlds" => ["Factions"],
                "desync-mitigation" => [
                    "enabled" => true,
                    "cooldown-seconds" => 0.35,
                ],
                "chunk-visualizer" => [
                    "density" => "medium",
                    "radius" => 1,
                    "base-interval-ticks" => 10,
                    "autoscale" => [
                        "enabled" => true,
                        "players-step" => 8,
                        "max-interval-multiplier" => 4,
                    ],
                ],
            ],
            "factions" => [
                "creation" => [
                    "disband-cooldown-seconds" => 86400,
                ],
                "name" => [
                    "enable-validation" => true,
                    "min-length" => 3,
                    "max-length" => 16,
                    "regex" => "/^[a-zA-Z0-9_]+$/",
                ],
                "members" => [
                    "max-per-faction" => 15,
                ],
            ],
            "alliances" => [
                "max-per-faction" => 2,
                "request-timeout-seconds" => 60,
                "member-friendly-fire" => false,
                "ally-friendly-fire" => false,
            ],
            "pvp" => [
                "bypass-worlds" => ["pvp"],
            ],
            "invite" => [
                "request-timeout-seconds" => 60,
            ],
            "power" => [
                "initial-power" => 2,
                "power-per-member" => 10,
                "power-per-kill" => 5,
                "power-per-death" => 5,
                "power-freeze-seconds" => 600,
                "regen-interval-minutes" => 5,
                "regen-amount" => 1,
            ],
            "home" => [
                "teleport-warmup-seconds" => 5,
            ],
            "cache" => [
                "warmup" => [
                    "enabled" => true,
                    "prioritize-online" => true,
                    "batch-size" => 25,
                    "interval-ticks" => 10,
                ],
                "faction" => [
                    "max-entries" => 1000,
                    "memory-threshold-mb" => 50,
                    "ttl-seconds" => [
                        "low" => 1800,
                        "medium" => 3600,
                        "high" => 300,
                    ],
                ],
                "invite" => [
                    "ttl-override-seconds" => 0,
                ],
            ],
        ];
    }

    public static function getEmptyWarmupMetrics(): array
    {
        return [
            'enabled' => false,
            'running' => false,
            'completed' => 0,
            'total' => 0,
            'remaining' => 0,
            'batch_size' => 0,
            'interval_ticks' => 0,
            'prioritize_online' => false,
        ];
    }

    private static function mergeRecursive(array $defaults, array $existing, bool &$hasChanges = false): array
    {
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $existing)) {
                $existing[$key] = $value;
                $hasChanges = true;
            } elseif (is_array($value) && is_array($existing[$key])) {
                $existing[$key] = self::mergeRecursive($value, $existing[$key], $hasChanges);
            }
        }
        return $existing;
    }
}
