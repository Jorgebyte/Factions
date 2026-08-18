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
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class DemoteCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("demote", "Demote a Co-Leader to Member");
        $this->setPermission(Permissions::FACTIONS_COMMAND_DEMOTE->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_DEMOTE->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $targetName = $args["player"];

        Await::f2c(function () use ($sender, $targetName) {
            /** @var Player $sender */
            $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::DEMOTE_NOT_IN_FACTION->value);
            if ($faction === null) {
                return;
            }

            if (!$this->checkRoleOrMessage($sender, $faction, [Role::LEADER], LangKeys::DEMOTE_NO_PERMISSION->value)) {
                return;
            }

            $targetMember = $this->getMemberByNameOrMessage($sender, $faction, $targetName, LangKeys::DEMOTE_TARGET_NOT_FOUND->value);
            if ($targetMember === null) {
                return;
            }

            if (!$this->validateTargetNotSelf($sender, $targetMember, LangKeys::DEMOTE_CANNOT_DEMOTE_SELF->value)) {
                return;
            }

            if (!$this->validateTargetRole($sender, $targetMember, Role::MEMBER, LangKeys::DEMOTE_ALREADY_LOWEST->value, false)) {
                return;
            }

            $demoteResponse = yield from $this->factionManager->demotePlayerResponse($targetMember);
            if (!$demoteResponse->isSuccess()) {
                $message = FactionResultLangMapper::toLangKey($demoteResponse->result, LangKeys::DEMOTE_FAILED);
                $sender->sendMessage(Lang::t($sender, $message->value));
                return;
            }

            $sender->sendMessage(Lang::t($sender, LangKeys::DEMOTE_SUCCESS->value, ["{player}" => $targetMember->playerName]));
            $targetPlayer = $sender->getServer()->getPlayerExact($targetMember->playerName);
            $targetPlayer?->sendMessage(Lang::t($targetPlayer, LangKeys::DEMOTE_TARGET_MESSAGE->value));
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
