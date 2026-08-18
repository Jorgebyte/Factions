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

namespace Jorgebyte\Factions\managers\faction;

use Jorgebyte\Factions\entities\Member;

final readonly class KickMemberRequest
{
    public function __construct(
        public string $actorXuid,
        public string $actorName,
        public Member $memberToKick,
    ) {
    }
}
