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

namespace Jorgebyte\Factions\entities;

final readonly class Claim
{
    public function __construct(
        public int $factionId,
        public int $chunkX,
        public int $chunkZ,
        public string $worldName,
    ) {
    }

    public function getChunkKey(): string
    {
        return $this->chunkX . ':' . $this->chunkZ . ':' . $this->worldName;
    }
}
