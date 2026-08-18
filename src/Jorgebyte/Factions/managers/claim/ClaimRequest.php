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

namespace Jorgebyte\Factions\managers\claim;

final readonly class ClaimRequest
{
    public function __construct(
        public int $chunkX,
        public int $chunkZ,
        public string $worldName,
        public int $spawnChunkX,
        public int $spawnChunkZ,
        public string $claimerXuid,
        public string $claimerName,
    ) {
    }
}
