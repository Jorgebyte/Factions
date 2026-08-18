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

namespace Jorgebyte\Factions\Tests\Unit;

use Jorgebyte\Factions\utils\Permissions;
use PHPUnit\Framework\TestCase;

final class PermissionsTest extends TestCase
{
    public function testPermissionValuesAreStrings(): void
    {
        foreach (Permissions::cases() as $permission) {
            $this->assertIsString($permission->value);
            $this->assertStringStartsWith('factions.command', $permission->value);
        }
    }

    public function testSpecificPermissionsExist(): void
    {
        $this->assertSame('factions.command', Permissions::FACTIONS_COMMAND->value);
        $this->assertSame('factions.command.create', Permissions::FACTIONS_COMMAND_CREATE->value);
        $this->assertSame('factions.command.disband', Permissions::FACTIONS_COMMAND_DISBAND->value);
        $this->assertSame('factions.command.claim', Permissions::FACTIONS_COMMAND_CLAIM->value);
    }
}
