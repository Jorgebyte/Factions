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

namespace Jorgebyte\Factions\command\subcommands\economy;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class MoneyCommand extends BaseSubCommand
{
    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("money", "Check faction balance", ["balance", "bal"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_MONEY->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_MONEY->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $faction = $this->factionManager->getPlayerFaction($sender->getXuid());

        if ($faction === null) {
            $sender->sendMessage(Lang::t($sender, LangKeys::GENERIC_NOT_IN_FACTION->value));
            return;
        }

        $sender->sendMessage(Lang::t($sender, LangKeys::BANK_BALANCE->value, ["{money}" => number_format($faction->money, 2)]));
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
