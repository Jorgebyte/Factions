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

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\application\shared\FactionResultLangMapper;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class DisbandCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly SessionManager $sessionManager,
    ) {
        parent::__construct("disband", "Disband Faction");
        $this->setPermission(Permissions::FACTIONS_COMMAND_DISBAND->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_DISBAND->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::GENERIC_NOT_IN_FACTION->value);
        if ($faction === null) {
            return;
        }

        if (!$this->checkRoleOrMessage($sender, $faction, [Role::LEADER, Role::COLEADER], LangKeys::DISBAND_NOT_LEADER->value)) {
            return;
        }

        $factionName = $faction->name;

        Await::f2c(function () use ($sender, $faction, $factionName) {
            $response = yield from $this->factionManager->disbandFactionResponse($faction);
            if (!$response->isSuccess()) {
                $message = FactionResultLangMapper::toLangKey(
                    $response->result,
                    LangKeys::DISBAND_CANCELLED
                );
                $sender->sendMessage(Lang::t($sender, $message->value));
                return;
            }

            $this->sessionManager->resetFactionChatContext($faction);
            $sender->sendMessage(Lang::t($sender, LangKeys::DISBAND_SUCCESS->value, ["{name}" => $factionName]));
        });
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
