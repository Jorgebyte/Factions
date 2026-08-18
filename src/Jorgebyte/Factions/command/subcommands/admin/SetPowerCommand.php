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

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\application\admin\FactionPowerActionService;
use Jorgebyte\Factions\command\args\FactionNameArgument;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use SOFe\AwaitGenerator\Await;

final class SetPowerCommand extends AbstractFactionActionCommand
{
    public function __construct(
        private readonly FactionPowerActionService $powerActionService,
        FactionManager $factionManager,
    ) {
        parent::__construct('setpower', 'Set faction power');
        $this->factionManager = $factionManager;
        $this->setPermission(Permissions::FACTIONS_COMMAND_SETPOWER->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_SETPOWER->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $factionName = (string) $args['faction'];
        $power = (int) $args['power'];

        Await::f2c(function () use ($sender, $factionName, $power) {
            $faction = yield from $this->loadFactionByNameOrMessage($sender, $factionName, LangKeys::ADMIN_POWER_FACTION_NOT_FOUND);
            if ($faction === null) {
                return;
            }

            $result = $this->powerActionService->setPower($faction, $power);
            $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
        });
    }

    /** @throws ArgumentOrderException */
    protected function prepare(): void
    {
        $this->registerArgument(0, new FactionNameArgument('faction'));
        $this->registerArgument(1, new IntegerArgument('power'));
    }
}
