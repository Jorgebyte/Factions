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

namespace Jorgebyte\Factions\command\subcommands\territory;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\application\territory\ChunkVisualizationService;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class ChunkCommand extends BaseSubCommand
{
    public function __construct(
        private readonly ChunkVisualizationService $chunkVisualizationService,
    ) {
        parent::__construct("chunk", "Toggle chunk visualization", ["visualize", "border"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_CHUNK->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_CHUNK->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $key = $this->chunkVisualizationService->toggle($sender);
        if ($key === null) {
            return;
        }

        $sender->sendMessage(Lang::t($sender, $key->value));
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
