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

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\application\relation\AllyActionService;
use Jorgebyte\Factions\command\args\FactionNameArgument;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class AllyCommand extends BaseSubCommand
{
    public function __construct(
        private readonly AllyActionService $allyActionService,
    ) {
        parent::__construct("ally", "Request or accept alliance");
        $this->setPermission(Permissions::FACTIONS_COMMAND_ALLY->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_ALLY->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $arg0 = $args["action_or_faction"];
        $arg1 = $args["faction"] ?? null;

        $action = strtolower($arg0);
        $isAccept = in_array($action, ["accept", "yes", "a"], true);
        $isDeny = in_array($action, ["deny", "no", "reject", "d"], true);

        Await::f2c(/**
         * @throws Throwable
         */ function () use ($sender, $action, $arg1, $isAccept, $isDeny, $arg0) {
            if ($isAccept || $isDeny) {
                if ($arg1 === null) {
                    $sender->sendMessage(Lang::t($sender, LangKeys::RELATION_ALLY_USAGE->value, ['{action}' => $action]));
                    return;
                }
                $targetName = $arg1;
            } else {
                $targetName = $arg0;
            }

            /** @var Player $sender */
            $precheck = yield from $this->allyActionService->precheck($sender, $targetName);
            if (!$precheck->isSuccess) {
                $error = $precheck->error;
                if ($error !== null) {
                    $sender->sendMessage(Lang::t($sender, $error->messageKey->value, $error->params));
                }
                return;
            }

            if ($precheck->myFaction === null || $precheck->targetFaction === null || $precheck->memberRole === null) {
                return;
            }

            if ($isAccept) {
                $result = yield from $this->allyActionService->accept($precheck->myFaction, $precheck->targetFaction, $precheck->memberRole);
                $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
                return;
            }

            if ($isDeny) {
                $result = yield from $this->allyActionService->deny($precheck->myFaction, $precheck->targetFaction);
                $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
                return;
            }

            $result = yield from $this->allyActionService->requestOrAutoAccept($precheck->myFaction, $precheck->targetFaction, $precheck->memberRole);
            $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument("action_or_faction"));
        $this->registerArgument(1, new FactionNameArgument("faction", true));
    }
}
