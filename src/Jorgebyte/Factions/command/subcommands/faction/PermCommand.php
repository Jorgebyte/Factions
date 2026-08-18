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
use Jorgebyte\Factions\application\audit\FactionAuditService;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\entities\enums\FactionPermission;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\event\faction\FactionPermissionChangeEvent;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class PermCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly DataConnector $connector,
        private readonly ?FactionAuditService $auditService = null
    ) {
        parent::__construct("perm", "Manage role permissions", ["permissions", "flag", "flags"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_PERM->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_PERM->value;
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
            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_NO_PERMISSION->value, ["{perm}" => "PERM_MANAGE"]));
            return;
        }

        $roleStr = strtolower(trim((string) ($args["role"] ?? "")));
        if ($roleStr === "") {
            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_USAGE->value));
            return;
        }

        $targetRole = Role::tryFrom($roleStr);
        if ($targetRole === null || $targetRole === Role::LEADER) {
            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_USAGE->value));
            return;
        }

        $permStr = strtolower(trim((string) ($args["permission"] ?? "")));
        if ($permStr === "") {
            // List permissions for role
            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_LIST_HEADER->value, ["{role}" => ucfirst($targetRole->value)]));
            foreach (FactionPermission::cases() as $perm) {
                $granted = $faction->permissions->hasPermission($targetRole, $perm);
                $statusStr = $granted ? "§a[ALLOWED]" : "§c[DENIED]";
                $sender->sendMessage(" §8- §f" . $perm->value . ": " . $statusStr . " §7(" . $perm->getDescription() . ")");
            }
            return;
        }

        $targetPerm = FactionPermission::tryFrom($permStr);
        if ($targetPerm === null) {
            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_USAGE->value));
            return;
        }

        $newValue = !$faction->permissions->hasPermission($targetRole, $targetPerm);

        $event = new FactionPermissionChangeEvent($faction, $sender, $targetRole, $targetPerm, $newValue);
        $event->call();
        if ($event->isCancelled()) {
            $sender->sendMessage(Lang::t($sender, LangKeys::ACTION_CANCELLED_BY_EVENT->value));
            return;
        }

        $faction->permissions->setPermission($targetRole, $targetPerm, $newValue);

        Await::f2c(function () use ($faction, $targetRole, $targetPerm, $newValue, $sender) {
            try {
                yield from $this->connector->asyncChange("faction_permissions.set", [
                    "faction_id" => $faction->id,
                    "role" => $targetRole->value,
                    "permission" => $targetPerm->value,
                    "granted" => $newValue ? 1 : 0,
                ]);
            } catch (Throwable) {
            }

            if ($this->auditService !== null) {
                $stateText = $newValue ? "ALLOWED" : "DENIED";
                yield from $this->auditService->log(
                    $faction,
                    $sender->getXuid(),
                    $sender->getName(),
                    "PERM_TOGGLE",
                    "Set permission '{$targetPerm->value}' for '{$targetRole->value}' to {$stateText}"
                );
            }

            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_TOGGLED->value, [
                "{perm}" => $targetPerm->value,
                "{role}" => ucfirst($targetRole->value),
                "{state}" => $newValue ? "§aALLOWED" : "§cDENIED",
            ]));
        });
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument("role", true));
        $this->registerArgument(1, new RawStringArgument("permission", true));
    }
}
