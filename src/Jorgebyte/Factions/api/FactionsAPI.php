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

namespace Jorgebyte\Factions\api;

use Generator;
use Jorgebyte\Factions\entities\Claim;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\entities\Member;
use Jorgebyte\Factions\managers\ally\AllyManager;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use RuntimeException;
use Throwable;

final class FactionsAPI
{
    private static ?FactionsAPI $instance = null;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly ClaimManager $claimManager,
        private readonly AllyManager $allyManager,
    ) {
        self::$instance = $this;
    }

    /**
     * Retrieves the singleton instance of the Factions API.
     *
     * @throws RuntimeException If the API has not been initialized.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException("FactionsAPI has not been initialized yet.");
        }
        return self::$instance;
    }

    /**
     * Retrieves a Faction by its unique ID.
     * This method is asynchronous friendly and should be yielded.
     *
     * @param int $id The unique identifier of the faction.
     *
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, Faction|null> The faction instance or null if not found.
     */
    public function getFactionById(int $id): Generator
    {
        return $this->factionManager->loadFaction($id);
    }

    /**
     * Retrieves a Faction by its name.
     * Note: This operation may query the database if the faction is not in cache.
     *
     * @param string $name The name of the faction (case-insensitive usually).
     *
     * @return Generator<mixed, mixed, mixed, Faction|null>
     */
    public function getFactionByName(string $name): Generator
    {
        return $this->factionManager->loadFactionByName($name);
    }

    /**
     * Loads a Faction by a player's XUID.
     * Safest and most accurate method to fetch a player's faction, resolving from cache or DB.
     *
     * @param string $xuid The player's XUID.
     *
     * @throws Throwable
     * @return Generator<mixed, mixed, mixed, Faction|null>
     */
    public function loadPlayerFaction(string $xuid): Generator
    {
        return $this->factionManager->loadFactionByPlayerXuid($xuid);
    }

    /**
     * Retrieves the Faction of a specific player instantly from the cache.
     * If the player's faction mapping is not cached, this will return null.
     *
     * @param Player|string $player The player instance or their XUID.
     *
     * @return Faction|null The faction the player belongs to, or null if none or not cached.
     */
    public function getPlayerFactionCached(Player|string $player): ?Faction
    {
        $xuid = $player instanceof Player ? $player->getXuid() : $player;
        return $this->factionManager->getPlayerFaction($xuid);
    }

    /**
     * Checks if a player is currently in any faction synchronously (from cache).
     *
     * @param Player|string $player The player instance or XUID.
     *
     * @return bool True if the player is mapped to a faction in memory.
     */
    public function isInFactionCached(Player|string $player): bool
    {
        return $this->getPlayerFactionCached($player) !== null;
    }

    /**
     * Retrieves a Faction Member object by player name.
     * Note: Iterates through cached factions. For strict lookups, prefer using XUIDs.
     *
     * @param string $playerName
     * @return Member|null
     */
    public function getMemberByName(string $playerName): ?Member
    {
        $player = Server::getInstance()->getPlayerExact($playerName);
        if ($player !== null) {
            $faction = $this->getPlayerFactionCached($player);
            return $faction?->getMember($player->getXuid());
        }

        foreach ($this->factionManager->getLoadedFactions() as $faction) {
            $member = $faction->getMemberByName($playerName);
            if ($member !== null) {
                return $member;
            }
        }
        return null;
    }

    /**
     * Checks if two factions are allied. Synchronous operation (reads from fast memory cache).
     *
     * @param int|Faction $faction1
     * @param int|Faction $faction2
     * @return bool
     */
    public function areAllied(int|Faction $faction1, int|Faction $faction2): bool
    {
        $id1 = $faction1 instanceof Faction ? $faction1->id : $faction1;
        $id2 = $faction2 instanceof Faction ? $faction2->id : $faction2;
        return $this->allyManager->areAllied($id1, $id2);
    }

    /**
     * Gets the Claim at a specific world position from the cache.
     *
     * @param Position $position
     * @return Claim|null
     */
    public function getClaimAt(Position $position): ?Claim
    {
        return $this->claimManager->getClaim(
            $position->getFloorX() >> 4,
            $position->getFloorZ() >> 4,
            $position->getWorld()->getFolderName(),
        );
    }

    /**
     * Checks if a position is claimed by any faction.
     */
    public function isClaimed(Position $position): bool
    {
        return $this->getClaimAt($position) !== null;
    }

    /**
     * Returns the Faction that owns the claim at the specified position.
     * Resolves synchronously from cache.
     */
    public function getFactionAt(Position $position): ?Faction
    {
        $claim = $this->getClaimAt($position);
        if ($claim === null) {
            return null;
        }
        return $this->factionManager->getFactionCache()->get($claim->factionId);
    }

    /**
     * Exposes the internal FactionManager.
     * Essential for accessing advanced features like bank deposits, leadership transfers, etc.
     */
    public function getFactionManager(): FactionManager
    {
        return $this->factionManager;
    }

    /**
     * Exposes the internal ClaimManager.
     */
    public function getClaimManager(): ClaimManager
    {
        return $this->claimManager;
    }

    /**
     * Exposes the internal AllyManager.
     */
    public function getAllyManager(): AllyManager
    {
        return $this->allyManager;
    }
}
