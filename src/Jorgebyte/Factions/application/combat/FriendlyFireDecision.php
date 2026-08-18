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

namespace Jorgebyte\Factions\application\combat;

use Jorgebyte\Factions\lang\LangKeys;
use pocketmine\player\Player;

final readonly class FriendlyFireDecision
{
    private function __construct(
        public bool $cancelDamage,
        public ?Player $notifier,
        public ?LangKeys $messageKey,
    ) {
    }

    public static function allow(): self
    {
        return new self(false, null, null);
    }

    public static function block(Player $notifier, LangKeys $messageKey): self
    {
        return new self(true, $notifier, $messageKey);
    }
}
