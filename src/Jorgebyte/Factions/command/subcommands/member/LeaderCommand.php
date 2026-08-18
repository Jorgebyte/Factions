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
use Jorgebyte\Factions\application\shared\FactionResultLangMapper;
use Jorgebyte\Factions\command\args\FactionMemberNameArgument;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

final class LeaderCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("leader", "Transfer faction leadership", ["transferleader", "transfer"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_LEADER->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_LEADER->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $targetName = $args["player"];

        Await::f2c(function () use ($sender, $targetName) {
            /** @var Player $sender */
            $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::GENERIC_NOT_IN_FACTION->value);
            if ($faction === null) {
                return;
            }

            if ($faction->leaderXuid !== $sender->getXuid()) {
                $sender->sendMessage(Lang::t($sender, LangKeys::LEADER_NOT_LEADER->value));
                return;
            }

            $targetMember = $this->getMemberByNameOrMessage($sender, $faction, $targetName, LangKeys::GENERIC_PLAYER_NOT_FOUND->value);
            if ($targetMember === null) {
                return;
            }

            if ($targetMember->playerXuid === $sender->getXuid()) {
                $sender->sendMessage(Lang::t($sender, LangKeys::LEADER_CANNOT_TRANSFER_SELF->value));
                return;
            }

            $senderMember = $faction->getMember($sender->getXuid());
            if ($senderMember === null) {
                $sender->sendMessage(Lang::t($sender, LangKeys::ACTION_CANCELLED_BY_EVENT->value));
                return;
            }

            $response = yield from $this->factionManager->transferLeadershipResponse($senderMember, $targetMember);
            if ($response->isSuccess()) {
                $sender->sendMessage(Lang::t($sender, LangKeys::LEADER_SUCCESS_SENDER->value, ["{player}" => $targetMember->playerName]));
                $targetPlayer = Server::getInstance()->getPlayerByPrefix($targetMember->playerName);
                $targetPlayer?->sendMessage(Lang::t($targetPlayer, LangKeys::LEADER_SUCCESS_RECEIVER->value));
                return;
            }

            $message = FactionResultLangMapper::toLangKey($response->result, LangKeys::LEADER_FAILED);
            $sender->sendMessage(Lang::t($sender, $message->value));
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new FactionMemberNameArgument("player"));
    }
}
