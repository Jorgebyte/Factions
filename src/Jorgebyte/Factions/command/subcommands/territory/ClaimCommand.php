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

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\application\territory\TerritoryActionService;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class ClaimCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly TerritoryActionService $territoryActionService,
    ) {
        parent::__construct("claim", "Claim the chunk you are standing on", ["claimchunk"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_CLAIM->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_CLAIM->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        Await::f2c(function () use ($sender) {
            /** @var Player $sender */
            $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::CLAIM_NOT_IN_FACTION->value);
            if ($faction === null) {
                return;
            }

            if (!$this->checkRoleOrMessage($sender, $faction, [Role::LEADER, Role::COLEADER], LangKeys::CLAIM_NO_PERMISSION->value)) {
                return;
            }

            $result = yield from $this->territoryActionService->claimChunk($sender, $faction);
            $sender->sendMessage(Lang::t($sender, $result->messageKey->value, $result->params));
        });
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
