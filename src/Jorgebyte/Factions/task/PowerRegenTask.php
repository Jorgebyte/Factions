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

namespace Jorgebyte\Factions\task;

use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\FactionConfig;
use pocketmine\scheduler\Task;

final class PowerRegenTask extends Task
{
    /** @var array<int, bool> Tracks which factions were frozen in the previous tick */
    private array $wasFrozenState = [];

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly FactionConfig $factionConfig,
    ) {
    }

    public function onRun(): void
    {
        $powerPerMember = $this->factionConfig->getPowerPerMember();
        $regenAmount = $this->factionConfig->getPowerRegenAmount();

        foreach ($this->factionManager->getLoadedFactions() as $faction) {
            $factionId = $faction->id;
            $currentlyFrozen = $faction->isPowerFrozen();
            $wasFrozen = $this->wasFrozenState[$factionId] ?? false;

            if ($currentlyFrozen) {
                $this->wasFrozenState[$factionId] = true;
                continue;
            }

            if ($wasFrozen) {
                $this->wasFrozenState[$factionId] = false;
                $faction->clearPowerFreeze();

                foreach ($faction->getOnlineMembers() as $memberPlayer) {
                    $memberPlayer->sendMessage(Lang::t($memberPlayer, LangKeys::FACTION_POWER_UNFROZEN->value));
                }
            }

            $onlineMembers = $faction->getOnlineMembers();
            if (empty($onlineMembers)) {
                continue;
            }

            $maxPower = $faction->getMembersCount() * $powerPerMember;
            if ($faction->power < $maxPower) {
                $newPower = min($maxPower, $faction->power + $regenAmount);
                if ($newPower !== $faction->power) {
                    $this->factionManager->updateFactionPower($faction, $newPower);
                }
            }
        }
    }
}
