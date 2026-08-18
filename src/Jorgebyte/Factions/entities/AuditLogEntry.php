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

namespace Jorgebyte\Factions\entities;

final readonly class AuditLogEntry
{
    public function __construct(
        public int $id,
        public int $factionId,
        public string $actorXuid,
        public string $actorName,
        public string $action,
        public string $details,
        public int $createdAt,
    ) {
    }

    public function getFormattedTime(): string
    {
        $diff = max(0, time() - $this->createdAt);
        if ($diff < 60) {
            return $diff . "s ago";
        }
        if ($diff < 3600) {
            return (int)($diff / 60) . "m ago";
        }
        if ($diff < 86400) {
            return (int)($diff / 3600) . "h ago";
        }
        return (int)($diff / 86400) . "d ago";
    }
}
