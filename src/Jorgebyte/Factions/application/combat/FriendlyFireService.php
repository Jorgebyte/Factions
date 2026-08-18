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
use Jorgebyte\Factions\managers\ally\AllyManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\FactionConfig;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\player\Player;

final readonly class FriendlyFireService
{
    private bool $memberFriendlyFireEnabled;

    private bool $allyFriendlyFireEnabled;

    /** @var array<string, true> */
    private array $pvpBypassWorldLookup;

    public function __construct(
        private FactionManager $factionManager,
        private AllyManager $allyManager,
        private FactionConfig $factionConfig,
    ) {
        $this->memberFriendlyFireEnabled = $this->factionConfig->isMemberFriendlyFireEnabled();
        $this->allyFriendlyFireEnabled = $this->factionConfig->isAllyFriendlyFireEnabled();
        $this->pvpBypassWorldLookup = $this->factionConfig->getPvpBypassWorldLookup();
    }

    public function evaluate(Entity $damager, Entity $target): FriendlyFireDecision
    {
        $attacker = $this->resolveAttacker($damager);
        if (!($attacker instanceof Player && $target instanceof Player)) {
            return FriendlyFireDecision::allow();
        }

        if ($this->isBypassWorld($target->getWorld()->getFolderName())) {
            return FriendlyFireDecision::allow();
        }

        $damagerFaction = $this->factionManager->getPlayerFaction($attacker->getXuid());
        $targetFaction = $this->factionManager->getPlayerFaction($target->getXuid());
        if ($damagerFaction === null || $targetFaction === null) {
            return FriendlyFireDecision::allow();
        }

        if (!$this->memberFriendlyFireEnabled && $damagerFaction->id === $targetFaction->id) {
            return FriendlyFireDecision::block($attacker, LangKeys::PVP_FACTION_MEMBER);
        }

        if ($this->allyFriendlyFireEnabled) {
            return FriendlyFireDecision::allow();
        }

        if ($this->allyManager->areAllied($damagerFaction->id, $targetFaction->id)) {
            return FriendlyFireDecision::block($attacker, LangKeys::PVP_ALLY_MEMBER);
        }

        return FriendlyFireDecision::allow();
    }

    private function isBypassWorld(string $worldName): bool
    {
        if ($this->pvpBypassWorldLookup === []) {
            return false;
        }

        return isset($this->pvpBypassWorldLookup[strtolower($worldName)]);
    }

    private function resolveAttacker(Entity $damager): ?Player
    {
        if ($damager instanceof Player) {
            return $damager;
        }

        if ($damager instanceof Projectile) {
            $owner = $damager->getOwningEntity();
            if ($owner instanceof Player) {
                return $owner;
            }
        }

        return null;
    }
}
