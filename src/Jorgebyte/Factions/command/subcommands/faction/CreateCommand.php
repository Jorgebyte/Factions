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
use Jorgebyte\Factions\application\shared\FactionResultLangMapper;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\event\faction\FactionCreateEvent;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\CreateFactionRequest;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class CreateCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("create", "Create Faction", ["c"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_CREATE->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_CREATE->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $name = trim((string) $args["name"]);

        if ($name === "") {
            $sender->sendMessage(Lang::t($sender, LangKeys::CREATE_NEED_NAME->value));
            return;
        }

        /** @var Player $sender */
        if (!$this->ensureNotInFaction($sender, $this->factionManager, LangKeys::CREATE_ALREADY_IN_FACTION->value)) {
            return;
        }

        Await::f2c(function () use ($sender, $name) {
            $cooldownRem = yield from $this->factionManager->getPlayerDisbandCooldownRemaining($sender->getXuid());
            if ($cooldownRem > 0 && !$sender->hasPermission("factions.bypass.cooldown")) {
                $hours = (int) ($cooldownRem / 3600);
                $minutes = (int) (($cooldownRem % 3600) / 60);
                $seconds = $cooldownRem % 60;
                $timeFormatted = sprintf("%02dh %02dm %02ds", $hours, $minutes, $seconds);

                $sender->sendMessage(Lang::t($sender, LangKeys::CREATE_COOLDOWN->value, ["{time}" => $timeFormatted]));
                return;
            }

            if ((yield from $this->factionManager->loadFactionByName($name)) !== null) {
                $sender->sendMessage(Lang::t($sender, LangKeys::CREATE_NAME_TAKEN->value, ["{name}" => $name]));
                return;
            }

            $event = new FactionCreateEvent($sender, $name);
            $event->call();
            if ($event->isCancelled()) {
                $sender->sendMessage(Lang::t($sender, LangKeys::ACTION_CANCELLED_BY_EVENT->value));
                return;
            }

            $request = new CreateFactionRequest(
                $event->getFactionName(),
                $sender->getXuid(),
                $sender->getName(),
            );

            $response = yield from $this->factionManager->createFaction($request);
            if (!$response->isSuccess()) {
                $messageKey = FactionResultLangMapper::forCreate($response->result);
                $sender->sendMessage(Lang::t($sender, $messageKey->value, ["{faction}" => $request->factionName]));
                return;
            }

            $sender->sendMessage(Lang::t($sender, LangKeys::CREATE_SUCCESS->value, ["{faction}" => $request->factionName]));
            $this->factionManager->syncPlayerDisplayByXuid($sender->getXuid());
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument("name"));
    }
}
