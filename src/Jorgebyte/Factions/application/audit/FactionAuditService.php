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

namespace Jorgebyte\Factions\application\audit;

use Generator;
use Jorgebyte\Factions\entities\AuditLogEntry;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\event\faction\FactionAuditEvent;
use poggit\libasynql\DataConnector;

final readonly class FactionAuditService
{
    public function __construct(private DataConnector $connector)
    {
    }

    /**
     * Log a faction audit event asynchronously.
     * @return Generator<mixed, mixed, mixed, void>
     */
    public function log(Faction $faction, string $actorXuid, string $actorName, string $action, string $details): Generator
    {
        $createdAt = time();
        try {
            yield from $this->connector->asyncInsert("faction_audit_logs.insert", [
                "faction_id" => $faction->id,
                "actor_xuid" => $actorXuid,
                "actor_name" => $actorName,
                "action" => $action,
                "details" => $details,
                "created_at" => $createdAt,
            ]);
        } catch (\Throwable) {
            // Non-critical audit failure
        }

        $entry = new AuditLogEntry(0, $faction->id, $actorXuid, $actorName, $action, $details, $createdAt);
        (new FactionAuditEvent($faction, $entry))->call();
    }

    /**
     * Fetch paginated audit entries for a faction.
     * @return Generator<mixed, mixed, mixed, array{entries: AuditLogEntry[], total: int, pages: int}>
     */
    public function getLogs(int $factionId, int $page = 1, int $perPage = 8): Generator
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $rows = yield from $this->connector->asyncSelect("faction_audit_logs.get_by_faction", [
            "faction_id" => $factionId,
            "limit" => $perPage,
            "offset" => $offset,
        ]);

        $countRows = yield from $this->connector->asyncSelect("faction_audit_logs.count_by_faction", [
            "faction_id" => $factionId,
        ]);

        $total = (int) ($countRows[0]['total'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));

        $entries = [];
        foreach ($rows as $row) {
            $entries[] = new AuditLogEntry(
                (int) $row['id'],
                (int) $row['faction_id'],
                (string) $row['actor_xuid'],
                (string) $row['actor_name'],
                (string) $row['action'],
                (string) $row['details'],
                (int) $row['created_at'],
            );
        }

        return [
            'entries' => $entries,
            'total' => $total,
            'pages' => $pages,
        ];
    }
}
