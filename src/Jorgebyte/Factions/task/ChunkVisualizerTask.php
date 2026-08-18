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

use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\utils\PlayerUtils;
use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\World;

final class ChunkVisualizerTask extends Task
{
    private DustParticle $greenParticle;

    private DustParticle $yellowParticle;

    private DustParticle $redParticle;

    private int $frame = 0;

    /** @var list<int> */
    private array $lineOffsets;

    public function __construct(
        private readonly SessionManager $sessionManager,
        private readonly ClaimManager $claimManager,
        private readonly FactionManager $factionManager,
    ) {
        $this->greenParticle = new DustParticle(new Color(0, 255, 0));
        $this->yellowParticle = new DustParticle(new Color(255, 215, 0));
        $this->redParticle = new DustParticle(new Color(255, 0, 0));

        $this->lineOffsets = [];
        for ($i = 0; $i <= 16; $i += 2) {
            $this->lineOffsets[] = $i;
        }
    }

    public function onRun(): void
    {
        $this->frame++;
        $pulse = ($this->frame & 1) === 0;

        foreach ($this->sessionManager->getSessionCache()->getSessions() as $session) {
            $player = PlayerUtils::getPlayerByXuid($session->xuid);
            if ($player === null) {
                continue;
            }

            if (!$session->visualizingChunks || !$player->isOnline()) {
                continue;
            }

            $playerFactionId = $this->factionManager->getPlayerFaction($session->xuid)?->id;

            $world = $player->getWorld();
            $worldName = $world->getFolderName();

            if (!$this->claimManager->isWorldAllowed($worldName)) {
                continue;
            }

            $chunkX = $player->getPosition()->getFloorX() >> 4;
            $chunkZ = $player->getPosition()->getFloorZ() >> 4;
            $y = $player->getPosition()->getY() + ($pulse ? 1.42 : 1.34);

            $minGridX = $chunkX - 1;
            $minGridZ = $chunkZ - 1;

            // Visualize 3x3 chunks
            for ($x = $chunkX - 1; $x <= $chunkX + 1; $x++) {
                for ($z = $chunkZ - 1; $z <= $chunkZ + 1; $z++) {
                    $claim = $this->claimManager->getClaim($x, $z, $worldName);
                    $particle = $this->greenParticle;
                    if ($claim !== null) {
                        $particle = ($playerFactionId !== null && $claim->factionId === $playerFactionId)
                            ? $this->yellowParticle
                            : $this->redParticle;
                    }

                    $minX = $x << 4;
                    $minZ = $z << 4;
                    if ($x === $minGridX) {
                        $this->drawVerticalLine($world, $player, $particle, $minX, $y, $minZ);
                    }
                    if ($z === $minGridZ) {
                        $this->drawHorizontalLine($world, $player, $particle, $minX, $y, $minZ);
                    }

                    $this->drawVerticalLine($world, $player, $particle, $minX + 16, $y, $minZ);
                    $this->drawHorizontalLine($world, $player, $particle, $minX, $y, $minZ + 16);
                }
            }
        }
    }

    private function drawHorizontalLine(World $world, Player $player, DustParticle $particle, int $startX, float $y, int $z): void
    {
        foreach ($this->lineOffsets as $offset) {
            $world->addParticle(new Vector3($startX + $offset, $y, $z), $particle, [$player]);
        }
    }

    private function drawVerticalLine(World $world, Player $player, DustParticle $particle, int $x, float $y, int $startZ): void
    {
        foreach ($this->lineOffsets as $offset) {
            $world->addParticle(new Vector3($x, $y, $startZ + $offset), $particle, [$player]);
        }
    }
}
