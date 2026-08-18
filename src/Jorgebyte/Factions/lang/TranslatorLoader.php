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

use IvanCraft623\languages\Language;
use IvanCraft623\languages\Translator;
use Jorgebyte\Factions\Main;

final class TranslatorLoader
{
    public static function loadFromFolder(Main $plugin): Translator
    {
        $default = (string) $plugin->getConfig()->get("default-language", Main::DEFAULT_LANGUAGE);
        $translator = new Translator($plugin);
        $path = $plugin->getDataFolder() . "languages" . DIRECTORY_SEPARATOR;
        $defaultLanguage = null;

        $files = glob($path . "*.ini");
        if ($files !== false) {
            foreach ($files as $file) {
                $locale = basename($file, ".ini");
                $data = parse_ini_file($file, false, INI_SCANNER_RAW);

                if (!is_array($data)) {
                    throw new \RuntimeException("Invalid language file: {$file}");
                }

                $language = new Language($locale, array_map('stripcslashes', $data));
                $translator->registerLanguage($language);
                if ($locale === $default) {
                    $defaultLanguage = $language;
                }
            }
        }

        if ($defaultLanguage !== null) {
            $translator->setDefaultLanguage($defaultLanguage);
        } else {
            $plugin->getLogger()->warning("Default language '{$default}' not found.");
        }

        return $translator;
    }
}
