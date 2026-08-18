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

namespace Jorgebyte\Factions\command\subcommands\member;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\application\member\InviteActionService;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class AcceptCommand extends BaseSubCommand
{
    public function __construct(
        private readonly InviteActionService $inviteActionService,
    ) {
        parent::__construct("accept", "Accept a faction invitation");
        $this->setPermission(Permissions::FACTIONS_COMMAND_ACCEPT->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_ACCEPT->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        Await::f2c(/**
         * @throws Throwable
         */ function () use ($sender) {
            $result = yield from $this->inviteActionService->accept($sender);
            $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
        });
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
