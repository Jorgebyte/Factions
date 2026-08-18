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
use Jorgebyte\Factions\application\shared\FactionResultLangMapper;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class LeaveCommand extends BaseSubCommand
{
    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly SessionManager $sessionManager,
    ) {
        parent::__construct("leave", "Leave your current faction", ["quit"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_LEAVE->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_LEAVE->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        Await::f2c(function () use ($sender) {
            /** @var Player $sender */
            $faction = $this->factionManager->getPlayerFaction($sender->getXuid());

            if ($faction === null) {
                $sender->sendMessage(Lang::t($sender, LangKeys::LEAVE_NOT_IN_FACTION->value));
                return;
            }

            $member = $faction->getMember($sender->getXuid());
            if ($member === null) {
                return;
            }

            if ($member->role === Role::LEADER) {
                $sender->sendMessage(Lang::t($sender, LangKeys::LEAVE_LEADER_CANNOT_LEAVE->value));
                return;
            }

            try {
                $leaveResponse = yield from $this->factionManager->leaveFactionResponse($member);
                if (!$leaveResponse->isSuccess()) {
                    $message = FactionResultLangMapper::toLangKey($leaveResponse->result, LangKeys::LEAVE_FAILED);
                    $sender->sendMessage(Lang::t($sender, $message->value));
                    return;
                }

                $this->sessionManager->resetChatContext($sender->getXuid());
                $sender->sendMessage(Lang::t($sender, LangKeys::LEAVE_SUCCESS->value));
            } catch (\Exception $e) {
                $sender->sendMessage(Lang::t($sender, LangKeys::LEAVE_FAILED->value));
            }
        });
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
