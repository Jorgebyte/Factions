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

final class DenyCommand extends BaseSubCommand
{
    public function __construct(private readonly InviteActionService $inviteActionService)
    {
        parent::__construct("deny", "Deny a faction invitation", ["decline"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_DENY->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_DENY->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $result = $this->inviteActionService->deny($sender);
        $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
