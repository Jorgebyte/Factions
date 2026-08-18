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

namespace Jorgebyte\Factions\command\subcommands\home;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\listener\HomeWarmupListener;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class HomeCommand extends BaseSubCommand
{
    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("home", "Teleport to faction home");
        $this->setPermission(Permissions::FACTIONS_COMMAND_HOME->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_HOME->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $faction = $this->factionManager->getPlayerFaction($sender->getXuid());
        if ($faction === null) {
            $sender->sendMessage(Lang::t($sender, LangKeys::HOME_NOT_IN_FACTION->value));
            return;
        }

        $home = $faction->getHome();
        if ($home === null) {
            $sender->sendMessage(Lang::t($sender, LangKeys::HOME_NOT_SET->value));
            return;
        }

        $warmup = $this->factionManager->getFactionConfig()->getHomeTeleportWarmupSeconds();
        $warmupListener = HomeWarmupListener::getInstance();

        if ($warmup > 0 && $warmupListener !== null && !$sender->hasPermission("factions.bypass.warmup")) {
            $warmupListener->startWarmup($sender, $home, $warmup);
        } else {
            $sender->teleport($home);
            $sender->sendMessage(Lang::t($sender, LangKeys::HOME_TELEPORTED->value));
        }
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
