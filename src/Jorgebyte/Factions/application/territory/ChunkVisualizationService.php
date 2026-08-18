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

namespace Jorgebyte\Factions\application\territory;

use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\session\SessionManager;
use pocketmine\player\Player;

final readonly class ChunkVisualizationService
{
    public function __construct(
        private SessionManager $sessionManager,
    ) {
    }

    public function toggle(Player $player): ?LangKeys
    {
        $session = $this->sessionManager->getSession($player->getXuid());
        if ($session === null) {
            return null;
        }

        $session->visualizingChunks = !$session->visualizingChunks;
        return $session->visualizingChunks ? LangKeys::CHUNK_VISUALIZE_ON : LangKeys::CHUNK_VISUALIZE_OFF;
    }
}
