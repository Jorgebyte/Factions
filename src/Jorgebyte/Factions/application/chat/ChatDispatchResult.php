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

namespace Jorgebyte\Factions\application\chat;

final readonly class ChatDispatchResult
{
    private function __construct(
        public bool $handled,
        public bool $resetToPublic,
    ) {
    }

    public static function passthrough(): self
    {
        return new self(false, false);
    }

    public static function handled(): self
    {
        return new self(true, false);
    }

    public static function resetToPublic(): self
    {
        return new self(false, true);
    }
}
