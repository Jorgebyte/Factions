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

namespace Jorgebyte\Factions\application\admin;

use Jorgebyte\Factions\application\shared\CommandResult;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;

final readonly class FactionPowerActionService
{
    public function __construct(
        private FactionManager $factionManager,
    ) {
    }

    public function setPower(Faction $faction, int $power): CommandResult
    {
        if ($power < 0) {
            return new CommandResult(LangKeys::ADMIN_POWER_INVALID_AMOUNT);
        }

        $this->factionManager->updateFactionPower($faction, $power);

        return new CommandResult(LangKeys::ADMIN_POWER_SET_SUCCESS, [
            '{faction}' => $faction->name,
            '{power}' => (string) $faction->power,
        ]);
    }

    public function addPower(Faction $faction, int $amount): CommandResult
    {
        if ($amount <= 0) {
            return new CommandResult(LangKeys::ADMIN_POWER_INVALID_AMOUNT);
        }

        $newPower = $faction->power + $amount;
        $this->factionManager->updateFactionPower($faction, $newPower);

        return new CommandResult(LangKeys::ADMIN_POWER_ADD_SUCCESS, [
            '{faction}' => $faction->name,
            '{amount}' => (string) $amount,
            '{power}' => (string) $faction->power,
        ]);
    }

    public function removePower(Faction $faction, int $amount): CommandResult
    {
        if ($amount <= 0) {
            return new CommandResult(LangKeys::ADMIN_POWER_INVALID_AMOUNT);
        }

        $removed = min($amount, $faction->power);
        $newPower = max(0, $faction->power - $amount);
        $this->factionManager->updateFactionPower($faction, $newPower);

        return new CommandResult(LangKeys::ADMIN_POWER_REMOVE_SUCCESS, [
            '{faction}' => $faction->name,
            '{amount}' => (string) $removed,
            '{power}' => (string) $faction->power,
        ]);
    }

    public function freezePower(Faction $faction, int $seconds): CommandResult
    {
        if ($seconds <= 0) {
            return new CommandResult(LangKeys::ADMIN_POWER_INVALID_AMOUNT);
        }

        $faction->setPowerFreeze($seconds);
        $this->factionManager->queueFactionSave($faction);
        $this->factionManager->syncFactionDisplay($faction);

        return new CommandResult(LangKeys::ADMIN_FREEZE_SUCCESS, [
            '{faction}' => $faction->name,
            '{time}' => (string) $seconds,
        ]);
    }

    public function unfreezePower(Faction $faction): CommandResult
    {
        $faction->clearPowerFreeze();
        $this->factionManager->queueFactionSave($faction);
        $this->factionManager->syncFactionDisplay($faction);

        return new CommandResult(LangKeys::ADMIN_UNFREEZE_SUCCESS, [
            '{faction}' => $faction->name,
        ]);
    }
}
