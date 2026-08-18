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

namespace Jorgebyte\Factions\command\subcommands\admin;

use CortexPE\Commando\BaseSubCommand;
use Generator;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use pocketmine\command\CommandSender;

abstract class AbstractFactionActionCommand extends BaseSubCommand
{
    protected FactionManager $factionManager;

    /** @return Generator<mixed, mixed, mixed, Faction|null> */
    protected function loadFactionByNameOrMessage(CommandSender $sender, string $factionName, LangKeys $messageKey): Generator
    {
        $faction = yield from $this->factionManager->loadFactionByName($factionName);
        if ($faction === null) {
            $sender->sendMessage(Lang::t($sender, $messageKey->value, ['{faction}' => $factionName]));
            return null;
        }

        return $faction;
    }
}
