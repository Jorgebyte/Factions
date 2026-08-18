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

namespace Jorgebyte\Factions\command\subcommands\relation;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\application\relation\AllyActionService;
use Jorgebyte\Factions\command\args\FactionNameArgument;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class NeutralCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(private readonly AllyActionService $allyActionService)
    {
        parent::__construct("neutral", "Revoke alliance or set relation to neutral");
        $this->setPermission(Permissions::FACTIONS_COMMAND_NEUTRAL->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_NEUTRAL->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $targetFactionName = $args["faction"];

        Await::f2c(/**
         * @throws Throwable
         */ function () use ($sender, $targetFactionName) {
            /** @var Player $sender */
            $precheck = yield from $this->allyActionService->precheck($sender, $targetFactionName);
            if (!$precheck->isSuccess) {
                $error = $precheck->error;
                if ($error !== null) {
                    $sender->sendMessage(Lang::t($sender, $error->messageKey->value, $error->params));
                }
                return;
            }

            if ($precheck->myFaction === null || $precheck->targetFaction === null) {
                return;
            }

            if (!$this->checkRoleOrMessage($sender, $precheck->myFaction, [Role::LEADER, Role::COLEADER], LangKeys::RELATION_NO_PERMISSION->value)) {
                return;
            }

            if (!$precheck->myFaction->isAlly($precheck->targetFaction->id)) {
                $sender->sendMessage(Lang::t($sender, LangKeys::RELATION_ALREADY_RELATION->value));
                return;
            }

            $result = yield from $this->allyActionService->neutral($precheck->myFaction, $precheck->targetFaction);
            $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new FactionNameArgument("faction"));
    }
}
