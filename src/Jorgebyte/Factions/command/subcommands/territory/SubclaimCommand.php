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

namespace Jorgebyte\Factions\command\subcommands\territory;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\application\territory\SubclaimService;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\entities\enums\FactionPermission;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class SubclaimCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly ClaimManager $claimManager,
        private readonly SubclaimService $subclaimService
    ) {
        parent::__construct("subclaim", "Manage role subclaims", ["subclaims"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_SUBCLAIM->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_SUBCLAIM->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::INFO_FACTION_NOT_FOUND->value);
        if ($faction === null) {
            return;
        }

        $action = strtolower(trim((string) ($args["action"] ?? "list")));

        if ($action === "list") {
            $subclaims = $faction->getSubclaims();
            if (empty($subclaims)) {
                $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_LIST_EMPTY->value));
                return;
            }

            $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_LIST_HEADER->value, ["{faction}" => $faction->name]));
            foreach ($subclaims as $sub) {
                $radius = (int) (($sub->maxX - $sub->minX) / 2);
                $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_LIST_ENTRY->value, [
                    "{name}" => $sub->name,
                    "{world}" => $sub->worldName,
                    "{min_role}" => ucfirst($sub->minRole->value),
                    "{radius}" => (string) $radius,
                ]));
            }
            return;
        }

        $name = trim((string) ($args["name"] ?? ""));
        if ($name === "") {
            $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_USAGE->value));
            return;
        }

        if ($action === "info") {
            $subclaim = $faction->getSubclaim($name);
            if ($subclaim === null) {
                $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_NOT_FOUND->value, ["{name}" => $name]));
                return;
            }

            $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_INFO_HEADER->value, ["{name}" => $subclaim->name]));
            $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_INFO_DETAILS->value, [
                "{world}" => $subclaim->worldName,
                "{min_role}" => ucfirst($subclaim->minRole->value),
                "{min_x}" => (string) $subclaim->minX,
                "{min_y}" => (string) $subclaim->minY,
                "{min_z}" => (string) $subclaim->minZ,
                "{max_x}" => (string) $subclaim->maxX,
                "{max_y}" => (string) $subclaim->maxY,
                "{max_z}" => (string) $subclaim->maxZ,
            ]));
            return;
        }

        // Actions below require SUBCLAIM permission
        $member = $faction->getMember($sender->getXuid());
        if ($member === null || !$faction->permissions->hasPermission($member->role, FactionPermission::SUBCLAIM)) {
            $sender->sendMessage(Lang::t($sender, LangKeys::PERM_NO_PERMISSION->value, ["{perm}" => "SUBCLAIM"]));
            return;
        }

        if ($action === "remove") {
            Await::f2c(function () use ($faction, $sender, $name) {
                $success = yield from $this->subclaimService->removeSubclaim($faction, $sender, $name);
                if ($success) {
                    $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_REMOVED->value, ["{name}" => $name]));
                } else {
                    $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_NOT_FOUND->value, ["{name}" => $name]));
                }
            });
            return;
        }

        if ($action === "setrole") {
            $roleStr = strtolower(trim((string) ($args["minRole"] ?? "")));
            $newRole = Role::tryFrom($roleStr);
            if ($newRole === null) {
                $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_USAGE->value));
                return;
            }

            Await::f2c(function () use ($faction, $sender, $name, $newRole) {
                $success = yield from $this->subclaimService->updateSubclaimRole($faction, $sender, $name, $newRole);
                if ($success) {
                    $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_ROLE_UPDATED->value, [
                        "{name}" => $name,
                        "{min_role}" => ucfirst($newRole->value),
                    ]));
                } else {
                    $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_NOT_FOUND->value, ["{name}" => $name]));
                }
            });
            return;
        }

        if ($action === "create") {
            // Must be inside faction claim
            $chunkX = $sender->getPosition()->getFloorX() >> 4;
            $chunkZ = $sender->getPosition()->getFloorZ() >> 4;
            $worldName = $sender->getWorld()->getFolderName();
            $claim = $this->claimManager->getClaim($chunkX, $chunkZ, $worldName);

            if ($claim === null || $claim->factionId !== $faction->id) {
                $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_MUST_BE_IN_CLAIM->value));
                return;
            }

            $roleStr = strtolower(trim((string) ($args["minRole"] ?? "coleader")));
            $minRole = Role::tryFrom($roleStr) ?? Role::COLEADER;
            $radius = max(1, min(8, (int) ($args["radius"] ?? 2)));

            Await::f2c(function () use ($faction, $sender, $name, $minRole, $radius) {
                $result = yield from $this->subclaimService->createSubclaim(
                    $faction,
                    $sender,
                    $name,
                    $sender->getPosition(),
                    $radius,
                    $minRole
                );

                match ($result) {
                    SubclaimService::RESULT_SUCCESS => $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_CREATED->value, [
                        "{name}" => $name,
                        "{min_role}" => ucfirst($minRole->value),
                    ])),
                    SubclaimService::RESULT_ALREADY_EXISTS => $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_ALREADY_EXISTS->value, [
                        "{name}" => $name,
                    ])),
                    SubclaimService::RESULT_OVERLAPS => $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_OVERLAPS->value, [
                        "{name}" => $name,
                    ])),
                    SubclaimService::RESULT_OUTSIDE_TERRITORY => $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_OUTSIDE_TERRITORY->value)),
                    default => $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_NOT_FOUND->value, [
                        "{name}" => $name,
                    ])),
                };
            });
            return;
        }

        $sender->sendMessage(Lang::t($sender, LangKeys::SUBCLAIM_USAGE->value));
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new RawStringArgument("action", true));
        $this->registerArgument(1, new RawStringArgument("name", true));
        $this->registerArgument(2, new RawStringArgument("minRole", true));
        $this->registerArgument(3, new IntegerArgument("radius", true));
    }
}
