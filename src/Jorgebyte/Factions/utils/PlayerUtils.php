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

use pocketmine\player\Player;
use pocketmine\Server;

final class PlayerUtils
{
    /** @var array<string, Player> */
    private static array $playersByXuid = [];

    public static function registerPlayer(Player $player): void
    {
        $xuid = $player->getXuid();
        if ($xuid !== '') {
            self::$playersByXuid[$xuid] = $player;
        }
    }

    public static function unregisterByXuid(string $xuid): void
    {
        unset(self::$playersByXuid[$xuid]);
    }

    public static function getPlayerByXuid(string $xuid): ?Player
    {
        $indexed = self::$playersByXuid[$xuid] ?? null;
        if ($indexed !== null && $indexed->isOnline()) {
            return $indexed;
        }

        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if ($onlinePlayer->getXuid() === $xuid) {
                self::$playersByXuid[$xuid] = $onlinePlayer;
                return $onlinePlayer;
            }
        }

        return null;
    }
}
