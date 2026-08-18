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

namespace Jorgebyte\Factions\listener;

use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\FactionConfig;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;

final readonly class CombatListener implements Listener
{
    public function __construct(
        private FactionManager $factionManager,
        private FactionConfig $factionConfig,
    ) {
    }

    /**
     * @priority NORMAL
     * @ignoreCanceled true
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void
    {
        $victim = $event->getPlayer();
        $victimXuid = $victim->getXuid();
        $victimFaction = $this->factionManager->getPlayerFaction($victimXuid);

        if ($victimFaction !== null) {
            $powerLoss = $this->factionConfig->getPowerPerDeath();
            $newVictimPower = max(0, $victimFaction->power - $powerLoss);
            $wasFrozen = $victimFaction->isPowerFrozen();

            $this->factionManager->updateFactionPower($victimFaction, $newVictimPower);

            // Check if power reached 0 and set freeze state
            if ($newVictimPower <= 0 && !$wasFrozen) {
                $freezeDuration = $this->factionConfig->getPowerFreezeSeconds();
                $victimFaction->setPowerFreeze($freezeDuration);
                $this->factionManager->queueFactionSave($victimFaction);

                $timeFormatted = sprintf("%02d:%02d", (int) ($freezeDuration / 60), $freezeDuration % 60);
                foreach ($victimFaction->getOnlineMembers() as $memberPlayer) {
                    $memberPlayer->sendMessage(Lang::t($memberPlayer, LangKeys::FACTION_POWER_FROZEN->value, [
                        "time" => $timeFormatted,
                    ]));
                }
            } else {
                foreach ($victimFaction->getOnlineMembers() as $memberPlayer) {
                    $memberPlayer->sendMessage(Lang::t($memberPlayer, LangKeys::FACTION_POWER_DEATH_LOSS->value, [
                        "player" => $victim->getName(),
                        "amount" => (string) $powerLoss,
                        "power" => (string) $victimFaction->power,
                    ]));
                }
            }
        }

        // Killer rewards
        $cause = $victim->getLastDamageCause();
        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if ($killer instanceof Player && $killer->getXuid() !== $victimXuid) {
                $killerFaction = $this->factionManager->getPlayerFaction($killer->getXuid());

                if ($killerFaction !== null && ($victimFaction === null || $killerFaction->id !== $victimFaction->id)) {
                    $killerFaction->addKill();
                    $powerGain = $this->factionConfig->getPowerPerKill();
                    $maxPower = $killerFaction->getMembersCount() * $this->factionConfig->getPowerPerMember();
                    $newKillerPower = min($maxPower, $killerFaction->power + $powerGain);

                    $this->factionManager->updateFactionPower($killerFaction, $newKillerPower);

                    foreach ($killerFaction->getOnlineMembers() as $memberPlayer) {
                        $memberPlayer->sendMessage(Lang::t($memberPlayer, LangKeys::FACTION_POWER_KILL_GAIN->value, [
                            "player" => $killer->getName(),
                            "amount" => (string) $powerGain,
                            "power" => (string) $killerFaction->power,
                        ]));
                    }
                }
            }
        }
    }
}
