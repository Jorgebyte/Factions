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
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\application\member\InviteActionService;
use Jorgebyte\Factions\command\args\OnlinePlayerArgument;
use Jorgebyte\Factions\event\faction\PlayerInviteEvent;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class InviteCommand extends BaseSubCommand
{
    public function __construct(
        private readonly InviteActionService $inviteActionService,
    ) {
        parent::__construct("invite", "Invite command");
        $this->setPermission(Permissions::FACTIONS_COMMAND_INVITE->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_INVITE->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $invitedPlayer = $args["player"] ?? null;

        if (!$invitedPlayer instanceof Player) {
            $sender->sendMessage(Lang::t($sender, LangKeys::GENERIC_PLAYER_NOT_FOUND->value));
            return;
        }

        $precheck = $this->inviteActionService->precheckInvite($sender, $invitedPlayer);
        if (!$precheck->isSuccess) {
            $error = $precheck->error;
            if ($error !== null) {
                $sender->sendMessage(Lang::t($sender, $error->messageKey->value, $error->params));
            }
            return;
        }

        $faction = $precheck->faction;
        if ($faction === null) {
            $sender->sendMessage(Lang::t($sender, LangKeys::INVITE_CANCELLED->value));
            return;
        }

        $event = new PlayerInviteEvent($faction, $sender, $invitedPlayer);
        $event->call();
        if ($event->isCancelled()) {
            $sender->sendMessage(Lang::t($sender, LangKeys::INVITE_CANCELLED->value));
            return;
        }

        $result = $this->inviteActionService->invite($sender, $invitedPlayer, $faction);
        $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));

        if ($result->messageKey === LangKeys::INVITE_SUCCESS_SENDER) {
            $invitedPlayer->sendMessage(Lang::t($invitedPlayer, LangKeys::INVITE_SUCCESS_RECEIVER->value, ["{faction}" => $faction->name]));
        }
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new OnlinePlayerArgument("player"));
    }
}
