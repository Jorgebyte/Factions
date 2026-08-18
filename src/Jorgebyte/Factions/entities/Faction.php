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

use Jorgebyte\Factions\utils\PlayerUtils;
use pocketmine\Server;
use pocketmine\world\Position;

final class Faction
{
    public readonly FactionPermissions $permissions;

    /** @var Member[] */
    private array $members = [];

    /** @var Claim[] */
    private array $claims = [];

    /** @var int[] */
    private array $allies = [];

    /** @var array<string, Subclaim> name -> Subclaim */
    private array $subclaims = [];

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $creationDate,
        public string $leaderXuid,
        public ?Position $home,
        public int $power,
        public float $money,
        private int $kills,
        public int $freezePowerTime = 0,
    ) {
        $this->permissions = new FactionPermissions();
    }

    /**
     * @return array<string, Subclaim>
     */
    public function getSubclaims(): array
    {
        return $this->subclaims;
    }

    public function getSubclaim(string $name): ?Subclaim
    {
        return $this->subclaims[strtolower($name)] ?? null;
    }

    public function addSubclaim(Subclaim $subclaim): void
    {
        $this->subclaims[strtolower($subclaim->name)] = $subclaim;
    }

    public function removeSubclaim(string $name): void
    {
        unset($this->subclaims[strtolower($name)]);
    }

    public function getSubclaimAt(Position $position): ?Subclaim
    {
        foreach ($this->subclaims as $subclaim) {
            if ($subclaim->contains($position)) {
                return $subclaim;
            }
        }
        return null;
    }

    public function getHome(): ?Position
    {
        return $this->home;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPowerFrozen(): bool
    {
        return time() < $this->freezePowerTime;
    }

    public function getFreezeTimeRemaining(): int
    {
        return max(0, $this->freezePowerTime - time());
    }

    public function setPowerFreeze(int $seconds): void
    {
        $this->freezePowerTime = time() + max(1, $seconds);
    }

    public function clearPowerFreeze(): void
    {
        $this->freezePowerTime = 0;
    }

    public function isRaidable(): bool
    {
        return $this->power <= 0 || $this->isPowerFrozen() || $this->power < $this->getClaimsCount();
    }

    public function getOnlineMembers(): array
    {
        $online = [];
        $server = Server::getInstance();
        foreach ($this->members as $member) {
            $p = PlayerUtils::getPlayerByXuid($member->playerXuid)
                ?? $server->getPlayerExact($member->getPlayerName());
            if ($p !== null && $p->isOnline()) {
                $online[] = $p;
            }
        }
        return $online;
    }

    /**
     * @return Member[]
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function getMembersCount(): int
    {
        return count($this->members);
    }

    /**
     * @return int[]
     */
    public function getAllies(): array
    {
        return $this->allies;
    }

    public function getAlliesCount(): int
    {
        return count($this->allies);
    }

    public function getKills(): int
    {
        return $this->kills;
    }

    public function setHome(?Position $home): void
    {
        $this->home = $home;
    }

    public function getClaimsCount(): int
    {
        return count($this->claims);
    }

    public function getMember(string $xuid): ?Member
    {
        return $this->members[$xuid] ?? null;
    }

    public function getMemberByName(string $name): ?Member
    {
        return array_find($this->members, fn ($member) => strcasecmp($member->getPlayerName(), $name) === 0);
    }

    public function addPower(int $amount): void
    {
        $this->power += $amount;
    }

    public function removePower(int $amount): void
    {
        $this->power -= $amount;
    }

    public function addMoney(float $amount): void
    {
        $this->money += $amount;
    }

    public function removeMoney(float $amount): void
    {
        $this->money -= $amount;
    }

    public function hasEnoughMoney(float $amount): bool
    {
        return $this->money >= $amount;
    }

    public function addKill(): void
    {
        $this->kills++;
    }

    public function addMember(Member $member): void
    {
        $this->members[$member->playerXuid] = $member;
    }

    public function removeMember(string $xuid): void
    {
        unset($this->members[$xuid]);
    }

    public function addAlly(int $factionId): void
    {
        if (!in_array($factionId, $this->allies, true)) {
            $this->allies[] = $factionId;
        }
    }

    public function removeAlly(int $factionId): void
    {
        $key = array_search($factionId, $this->allies, true);
        if ($key !== false) {
            unset($this->allies[$key]);
            $this->allies = array_values($this->allies);
        }
    }

    public function isAlly(int $factionId): bool
    {
        return in_array($factionId, $this->allies, true);
    }

    public function addClaim(Claim $claim): void
    {
        $this->claims[$claim->getChunkKey()] = $claim;
    }

    public function removeClaim(string $chunkKey): void
    {
        unset($this->claims[$chunkKey]);
    }
}
