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

use pocketmine\Server;
use pocketmine\world\Position;

final class PositionSerializer
{
    public static function serialize(?Position $position): ?string
    {
        if ($position === null || !$position->isValid()) {
            return null;
        }
        return implode(':', [
            round($position->getX(), 2),
            round($position->getY(), 2),
            round($position->getZ(), 2),
            $position->getWorld()->getFolderName(),
        ]);
    }

    public static function deserialize(?string $data): ?Position
    {
        if ($data === null || $data === '') {
            return null;
        }
        $parts = explode(':', $data);
        if (count($parts) !== 4) {
            return null;
        }

        if (!is_numeric($parts[0]) || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
            return null;
        }

        $worldName = $parts[3];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if ($world === null) {
            Server::getInstance()->getWorldManager()->loadWorld($worldName);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        }

        if ($world === null) {
            return null;
        }

        return new Position((float) $parts[0], (float) $parts[1], (float) $parts[2], $world);
    }
}
