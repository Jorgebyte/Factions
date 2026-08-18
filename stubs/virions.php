<?php

declare(strict_types=1);

namespace SOFe\AwaitGenerator {
    final class Await
    {
        public static function f2c(\Closure $generatorFactory): void
        {
        }

        public static function promise(\Closure $executor): \Generator
        {
            if (false) {
                yield null;
            }
            return null;
        }

        /**
         * @param array<int|string, mixed> $promises
         */
        public static function all(array $promises): \Generator
        {
            if (false) {
                yield null;
            }
            return [];
        }
    }
}

namespace poggit\libasynql {
    interface DataConnector
    {
        /**
         * @param array<string, mixed> $args
         */
        public function asyncSelect(string $queryName, array $args = []): \Generator;

        /**
         * @param array<string, mixed> $args
         */
        public function asyncInsert(string $queryName, array $args = []): \Generator;

        /**
         * @param array<string, mixed> $args
         */
        public function asyncChange(string $queryName, array $args = []): \Generator;

        /**
         * @param array<string, mixed> $args
         */
        public function asyncGeneric(string $queryName, array $args = []): \Generator;

        /**
         * @param array<string, mixed> $args
         */
        public function executeChange(string $queryName, array $args = []): void;

        public function close(): void;
    }

    final class libasynql
    {
        /**
         * @param array<string, mixed> $databaseConfig
         * @param array<string, string> $sqlFileMap
         */
        public static function create(object $plugin, array $databaseConfig, array $sqlFileMap): DataConnector
        {
            throw new \RuntimeException();
        }
    }
}

namespace DaPigGuy\libPiggyEconomy\providers {
    interface EconomyProvider
    {
        public function getMoney(\pocketmine\player\Player|string $player, \Closure $onSuccess): void;

        public function takeMoney(\pocketmine\player\Player|string $player, float $amount, \Closure $onSuccess): void;

        public function giveMoney(\pocketmine\player\Player|string $player, float $amount, \Closure $onSuccess): void;
    }
}

namespace DaPigGuy\libPiggyEconomy {
    final class libPiggyEconomy
    {
        public static function init(): void
        {
        }

        public static function getProvider(string $economyName): \DaPigGuy\libPiggyEconomy\providers\EconomyProvider
        {
            throw new \RuntimeException();
        }
    }
}

namespace IvanCraft623\languages {
    final class Language
    {
        /**
         * @param array<string, string> $messages
         */
        public function __construct(string $locale, array $messages)
        {
        }
    }

    final class Translator
    {
        public function __construct(\pocketmine\plugin\PluginBase $plugin)
        {
        }

        public function registerLanguage(Language $language): void
        {
        }

        public function setDefaultLanguage(Language $defaultLanguage): void
        {
        }

        public function getLanguage(): string
        {
            return 'en_US';
        }

        /**
         * @param array<string, mixed> $replacements
         */
        public function translate(mixed $target, string $key, array $replacements = []): string
        {
            return $key;
        }
    }
}
