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

namespace Jorgebyte\Factions\managers\session;

use Jorgebyte\Factions\cache\SessionMemoryCache;
use Jorgebyte\Factions\entities\enums\ChatMode;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\FactionSession;

final readonly class SessionManager
{
    private SessionMemoryCache $cache;

    public function __construct()
    {
        $this->cache = new SessionMemoryCache();
    }

    public function createSession(string $xuid): FactionSession
    {
        $existing = $this->cache->get($xuid);
        if ($existing !== null) {
            return $existing;
        }

        $session = new FactionSession($xuid);
        $this->cache->set($xuid, $session);
        return $session;
    }

    public function getSession(string $xuid): ?FactionSession
    {
        return $this->cache->get($xuid);
    }

    public function getSessionCache(): SessionMemoryCache
    {
        return $this->cache;
    }

    public function closeSession(string $xuid): void
    {
        $this->cache->remove($xuid);
    }

    public function resetChatContext(string $xuid): void
    {
        $session = $this->cache->get($xuid);
        if ($session === null) {
            return;
        }

        $session->setChatMode(ChatMode::PUBLIC);
    }

    public function resetFactionChatContext(Faction $faction): void
    {
        foreach ($faction->getMembers() as $member) {
            $this->resetChatContext($member->playerXuid);
        }
    }
}
