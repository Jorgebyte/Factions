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

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\application\audit\FactionAuditService;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class AuditCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly FactionAuditService $auditService
    ) {
        parent::__construct("log", "View faction audit logs", ["logs", "audit", "history"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_LOG->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_LOG->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::INFO_FACTION_NOT_FOUND->value);
        if ($faction === null) {
            return;
        }

        $member = $faction->getMember($sender->getXuid());
        if ($member === null || !$member->role->isAtLeast(Role::COLEADER)) {
            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_NO_PERMISSION->value, ["{perm}" => "AUDIT_LOGS"]));
            return;
        }

        $page = max(1, (int) ($args["page"] ?? 1));

        Await::f2c(function () use ($faction, $sender, $page) {
            $data = yield from $this->auditService->getLogs($faction->id, $page);
            $entries = $data['entries'];
            $totalPages = $data['pages'];

            if (empty($entries)) {
                $sender->sendMessage(Lang::t($sender, LangKeys::AUDIT_EMPTY->value));
                return;
            }

            $sender->sendMessage(Lang::t($sender, LangKeys::AUDIT_HEADER->value, [
                "{faction}" => $faction->name,
                "{page}" => (string) $page,
                "{total}" => (string) $totalPages,
            ]));

            foreach ($entries as $entry) {
                $sender->sendMessage(Lang::t($sender, LangKeys::AUDIT_ENTRY->value, [
                    "{time}" => $entry->getFormattedTime(),
                    "{actor}" => $entry->actorName,
                    "{details}" => $entry->details,
                ]));
            }
        });
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new IntegerArgument("page", true));
    }
}
