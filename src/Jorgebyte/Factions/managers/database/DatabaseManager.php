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

namespace Jorgebyte\Factions\managers\database;

use Generator;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final readonly class DatabaseManager
{
    private DataConnector $connector;

    /**
     * @param array<string, mixed> $databaseConfig
     */
    public function __construct(PluginBase $plugin, array $databaseConfig)
    {
        $this->connector = libasynql::create($plugin, $databaseConfig, [
            "sqlite" => "sql/sqlite.sql",
            "mysql" => "sql/mysql.sql",
        ]);
    }

    /**
     * @return Generator<mixed, mixed, mixed, void>
     */
    public function init(): Generator
    {
        yield from $this->initializeTables();
    }

    public function getConnector(): DataConnector
    {
        return $this->connector;
    }

    public function close(): void
    {
        $this->connector->close();
    }

    /**
     * @return Generator<mixed, mixed, mixed, void>
     */
    private function initializeTables(): Generator
    {
        $queries = [
            'factions.table',
            'members.table',
            'alliances.table',
            'claims.table',
            'player_cooldowns.table',
            'faction_permissions.table',
            'subclaims.table',
            'faction_audit_logs.table',
            'indices.create_player_xuid_index',
            'indices.create_faction_id_index',
            'indices.create_claims_faction_id_index',
            'indices.create_alliances_ally_id_index',
            'indices.create_factions_power_index',
            'indices.create_factions_money_index',
            'indices.create_factions_kills_index',
        ];

        foreach ($queries as $query) {
            try {
                yield from $this->connector->asyncGeneric($query, []);
            } catch (\Throwable $ignored) {
                // Ignore index/table creation warnings
            }
        }
    }
}
