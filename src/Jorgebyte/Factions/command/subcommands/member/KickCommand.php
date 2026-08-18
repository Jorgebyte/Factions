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
use Jorgebyte\Factions\managers\faction\KickMemberRequest;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class KickCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("kick", "Kick a member from the faction");
        $this->setPermission(Permissions::FACTIONS_COMMAND_KICK->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_KICK->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $targetName = $args["player"];

        Await::f2c(function () use ($sender, $targetName) {
            /** @var Player $sender */
            $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::KICK_NOT_IN_FACTION->value);
            if ($faction === null) {
                return;
            }

            if (!$this->checkRoleOrMessage($sender, $faction, [Role::LEADER, Role::COLEADER], LangKeys::KICK_NO_PERMISSION->value)) {
                return;
            }

            $kickerMember = $faction->getMember($sender->getXuid());
            if ($kickerMember === null) {
                return;
            }

            $targetMember = $this->getMemberByNameOrMessage($sender, $faction, $targetName, LangKeys::KICK_TARGET_NOT_FOUND->value);
            if ($targetMember === null) {
                return;
            }

            if ($targetMember->playerXuid === $sender->getXuid()) {
                return;
            }

            if (!$kickerMember->role->isHigherThan($targetMember->role)) {
                $sender->sendMessage(Lang::t($sender, LangKeys::KICK_CANNOT_KICK_HIGHER->value));
                return;
            }

            $kickResponse = yield from $this->factionManager->kickMemberResponse(new KickMemberRequest(
                $sender->getXuid(),
                $sender->getName(),
                $targetMember,
            ));
            if ($kickResponse->isSuccess()) {
                $sender->sendMessage(Lang::t($sender, LangKeys::KICK_SUCCESS->value, ["{player}" => $targetMember->playerName]));

                $targetPlayer = \pocketmine\Server::getInstance()->getPlayerExact($targetMember->playerName);
                $targetPlayer?->sendMessage(Lang::t($targetPlayer, LangKeys::KICK_TARGET_MESSAGE->value));
                return;
            }

            $message = FactionResultLangMapper::toLangKey($kickResponse->result, LangKeys::KICK_FAILED);
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
