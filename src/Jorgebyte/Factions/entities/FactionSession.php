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

use Jorgebyte\Factions\entities\enums\ChatMode;

final class FactionSession
{
    public readonly string $xuid;

    public ChatMode $chatMode = ChatMode::PUBLIC;

    public bool $visualizingChunks = false;

    private int $lastKillTime = 0;

    private int $killStreak = 0;

    public function __construct(string $xuid)
    {
        $this->xuid = $xuid;
    }

    public function setChatMode(ChatMode $mode): void
    {
        $this->chatMode = $mode;
    }

    public function addKill(): void
    {
        $currentTime = time();

        if ($currentTime - $this->lastKillTime > 60) {
            $this->killStreak = 0;
        }

        $this->killStreak++;
        $this->lastKillTime = $currentTime;
    }
}
