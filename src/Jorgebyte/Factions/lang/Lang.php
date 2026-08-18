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

namespace Jorgebyte\Factions\lang;

use IvanCraft623\languages\Translator;
use pocketmine\command\CommandSender;

final class Lang
{
    private static ?Translator $translator = null;

    public static function init(Translator $translator): void
    {
        self::$translator = $translator;
    }

    public static function t(?CommandSender $target, string $key, array $replacements = []): string
    {
        if (self::$translator === null) {
            throw new \RuntimeException("Translator not initialized");
        }
        return self::$translator->translate($target, $key, $replacements);
    }

    public static function tn(string $key, array $replacements = []): string
    {
        return self::t(null, $key, $replacements);
    }

    public static function get(): Translator
    {
        return self::$translator ?? throw new \RuntimeException("Translator not initialized");
    }
}
