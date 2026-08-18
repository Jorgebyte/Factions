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

namespace {
    // PSR-4 Autoloader for Jorgebyte\Factions
    spl_autoload_register(function (string $class): void {
        $prefix = 'Jorgebyte\\Factions\\';
        $baseDir = __DIR__ . '/src/Jorgebyte/Factions/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });

    // Load PocketMine-MP / Composer Autoloader
    $candidateAutoloaders = [
        __DIR__ . '/vendor/autoload.php',
        dirname(__DIR__, 2) . '/vendor/autoload.php',
        'phar://' . __DIR__ . '/PocketMine-MP.phar/vendor/autoload.php',
        'phar://' . dirname(__DIR__, 2) . '/PocketMine-MP.phar/vendor/autoload.php',
    ];
    foreach ($candidateAutoloaders as $autoloader) {
        if (file_exists($autoloader)) {
            require_once $autoloader;
            break;
        }
    }

    // Build a classmap for Virions
    $virionClassmap = [];
    $candidateDirs = array_filter([
        getenv('VIRIONS_DIR') ?: null,
        __DIR__ . '/.virions',
        __DIR__ . '/virions',
        dirname(__DIR__, 2) . '/virions',
    ]);

    foreach ($candidateDirs as $virionsDir) {
        if (!is_dir($virionsDir)) {
            continue;
        }

        foreach (glob($virionsDir . '/*.phar') as $pharPath) {
            try {
                $phar = new Phar($pharPath);
                foreach (new RecursiveIteratorIterator($phar) as $file) {
                    $path = str_replace('\\', '/', $file->getPathname());
                    if ($file->getExtension() === 'php' && str_contains($path, '/src/')) {
                        $content = file_get_contents($file->getPathname());
                        if ($content === false) {
                            continue;
                        }

                        if (preg_match('/namespace\s+([^;]+);/', $content, $m)) {
                            $namespace = trim($m[1]);
                            if (preg_match_all('/(?:class|interface|trait|enum)\s+([a-zA-Z0-9_]+)/', $content, $matches)) {
                                foreach ($matches[1] as $name) {
                                    $fqcn = $namespace !== '' ? $namespace . '\\' . $name : $name;
                                    if (!isset($virionClassmap[$fqcn])) {
                                        $virionClassmap[$fqcn] = $file->getPathname();
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }

    spl_autoload_register(function (string $class) use (&$virionClassmap): void {
        if (isset($virionClassmap[$class])) {
            require_once $virionClassmap[$class];
            return;
        }

        // Handle Poggit virion shaded namespaces (e.g. SOFe\AwaitGenerator)
        if (str_starts_with($class, 'SOFe\\AwaitGenerator\\')) {
            $short = substr($class, strlen('SOFe\\AwaitGenerator\\'));
            $shadedVariants = [
                'poggit\\libasynql\\libs\\SOFe\\AwaitGenerator\\' . $short,
                'SOFe\\AwaitStd\\_95be455e\\SOFe\\AwaitGenerator\\' . $short,
            ];
            foreach ($shadedVariants as $variant) {
                if (isset($virionClassmap[$variant])) {
                    require_once $virionClassmap[$variant];
                    if (class_exists($variant, false) || interface_exists($variant, false) || trait_exists($variant, false)) {
                        class_alias($variant, $class);
                        return;
                    }
                }
            }
        }
    });

    // PHPUnit Stub for PHPStan if PHPUnit vendor not present
    if (!class_exists(\PHPUnit\Framework\TestCase::class)) {
        abstract class PHPUnit_Framework_TestCase_Stub
        {
            public static function assertSame(mixed $expected, mixed $actual, string $message = ''): void
            {
            }

            public static function assertNotSame(mixed $expected, mixed $actual, string $message = ''): void
            {
            }

            public static function assertTrue(mixed $condition, string $message = ''): void
            {
            }

            public static function assertFalse(mixed $condition, string $message = ''): void
            {
            }

            public static function assertIsString(mixed $actual, string $message = ''): void
            {
            }

            public static function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
            {
            }
        }
        class_alias(PHPUnit_Framework_TestCase_Stub::class, \PHPUnit\Framework\TestCase::class);
    }
}

namespace Ifera\ScoreHud\scoreboard {
    if (!class_exists(ScoreTag::class)) {
        final class ScoreTag
        {
            public function __construct(string $name, string $value)
            {
            }

            public function getName(): string
            {
                return '';
            }

            public function getValue(): string
            {
                return '';
            }

            public function setValue(string $value): void
            {
            }
        }
    }
}

namespace Ifera\ScoreHud\event {
    if (!class_exists(TagsResolveEvent::class)) {
        final class TagsResolveEvent
        {
            public function getTag(): \Ifera\ScoreHud\scoreboard\ScoreTag
            {
                return new \Ifera\ScoreHud\scoreboard\ScoreTag('', '');
            }

            public function getPlayer(): \pocketmine\player\Player
            {
                return new \pocketmine\player\Player();
            }
        }
    }
    if (!class_exists(PlayerTagsUpdateEvent::class)) {
        final class PlayerTagsUpdateEvent
        {
            public function __construct(\pocketmine\player\Player $player, array $tags = [])
            {
            }

            public function call(): void
            {
            }
        }
    }
}

namespace IvanCraft623\RankSystem {
    if (!class_exists(RankSystem::class)) {
        final class RankSystem
        {
            public static function getInstance(): self
            {
                return new self();
            }

            public function getSessionManager(): \IvanCraft623\RankSystem\session\SessionManager
            {
                return new \IvanCraft623\RankSystem\session\SessionManager();
            }

            public function getTagManager(): \IvanCraft623\RankSystem\tag\TagManager
            {
                return new \IvanCraft623\RankSystem\tag\TagManager();
            }
        }
    }
}

namespace IvanCraft623\RankSystem\rank {
    if (!class_exists(Rank::class)) {
        final class Rank
        {
            public function getName(): string
            {
                return '';
            }

            public function getChatFormat(): array
            {
                return [];
            }

            public function getNameTagFormat(): array
            {
                return [];
            }
        }
    }
}

namespace IvanCraft623\RankSystem\session {
    if (!class_exists(SessionManager::class)) {
        final class SessionManager
        {
            public static function getInstance(): self
            {
                return new self();
            }

            public function get(mixed $player): ?Session
            {
                return new Session();
            }
        }
    }
    if (!class_exists(Session::class)) {
        final class Session
        {
            public function getHighestRank(): \IvanCraft623\RankSystem\rank\Rank
            {
                return new \IvanCraft623\RankSystem\rank\Rank();
            }

            public function getPlayer(): \pocketmine\player\Player
            {
                return new \pocketmine\player\Player();
            }

            public function updateNameTag(): void
            {
            }
        }
    }
}

namespace IvanCraft623\RankSystem\tag {
    if (!class_exists(TagManager::class)) {
        final class TagManager
        {
            public static function getInstance(): self
            {
                return new self();
            }

            public function registerTag(mixed ...$args): void
            {
            }
        }
    }

    if (!class_exists(Tag::class)) {
        final class Tag
        {
            public function __construct(string $name, \Closure $getValue)
            {
            }
        }
    }
}
