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

namespace Jorgebyte\Factions\command\subcommands\faction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\entities\enums\ChatMode;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class ChatCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(
        private readonly SessionManager $sessionManager,
        private readonly FactionManager $factionManager,
    ) {
        parent::__construct("chat", "Toggle chat mode");
        $this->setPermission(Permissions::FACTIONS_COMMAND_CHAT->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_CHAT->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        Await::f2c(function () use ($sender, $args) {
            $xuid = $sender->getXuid();
            $session = $this->sessionManager->getSession($xuid) ?? $this->sessionManager->createSession($xuid);
            $faction = $this->factionManager->getPlayerFaction($xuid);

            if ($faction === null) {
                $faction = yield from $this->factionManager->loadFactionByPlayerXuid($xuid);
            }

            if ($faction === null) {
                $this->sessionManager->resetChatContext($xuid);
                $sender->sendMessage(Lang::t($sender, LangKeys::GENERIC_NOT_IN_FACTION->value));
                return;
            }

            $mode = $args["mode"] ?? null;

            if ($mode === null) {
                $newMode = $session->chatMode->next();
            } else {
                $newMode = ChatMode::fromAlias($mode);

                if ($newMode === null) {
                    $sender->sendMessage(Lang::t($sender, LangKeys::CHAT_INVALID_MODE->value));
                    return;
                }
            }

            $session->setChatMode($newMode);

            $msgKey = match ($newMode) {
                ChatMode::FACTION => LangKeys::CHAT_MODE_FACTION,
                ChatMode::ALLY => LangKeys::CHAT_MODE_ALLY,
                default => LangKeys::CHAT_MODE_PUBLIC,
            };

            $sender->sendMessage(Lang::t($sender, $msgKey->value));
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument("mode", true));
    }
}
