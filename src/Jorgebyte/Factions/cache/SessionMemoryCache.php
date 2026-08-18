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

use Jorgebyte\Factions\entities\FactionSession;

final class SessionMemoryCache
{
    /** @var array<string, SessionCacheEntry> */
    private array $sessions = [];

    public function get(string $xuid): ?FactionSession
    {
        return isset($this->sessions[$xuid]) ? $this->sessions[$xuid]->session : null;
    }

    public function getEntry(string $xuid): ?SessionCacheEntry
    {
        return $this->sessions[$xuid] ?? null;
    }

    public function set(string $xuid, FactionSession $session): void
    {
        $this->sessions[$xuid] = new SessionCacheEntry($session, time());
    }

    public function remove(string $xuid): void
    {
        unset($this->sessions[$xuid]);
    }

    public function has(string $xuid): bool
    {
        return isset($this->sessions[$xuid]);
    }

    /**
     * @return FactionSession[]
     */
    public function getSessions(): array
    {
        $result = [];
        foreach ($this->sessions as $entry) {
            $result[] = $entry->session;
        }
        return $result;
    }
}
