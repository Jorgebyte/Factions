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

final readonly class ClaimResponse
{
    /** @param array<string, mixed> $context */
    private function __construct(
        public ClaimResult $result,
        public array $context = [],
    ) {
    }

    /** @param array<string, mixed> $context */
    public static function success(array $context = []): self
    {
        return new self(ClaimResult::SUCCESS, $context);
    }

    /** @param array<string, mixed> $context */
    public static function fail(ClaimResult $result, array $context = []): self
    {
        return new self($result, $context);
    }

    public function isSuccess(): bool
    {
        return $this->result === ClaimResult::SUCCESS;
    }
}
