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

namespace Jorgebyte\Factions\command;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\command\subcommands\admin\AddPowerCommand;
use Jorgebyte\Factions\command\subcommands\admin\CacheCommand;
use Jorgebyte\Factions\command\subcommands\admin\FreezePowerCommand;
use Jorgebyte\Factions\command\subcommands\admin\RemovePowerCommand;
use Jorgebyte\Factions\command\subcommands\admin\SetPowerCommand;
use Jorgebyte\Factions\command\subcommands\admin\UnfreezePowerCommand;
use Jorgebyte\Factions\command\subcommands\economy\DepositCommand;
use Jorgebyte\Factions\command\subcommands\economy\MoneyCommand;
use Jorgebyte\Factions\command\subcommands\economy\WithdrawCommand;
use Jorgebyte\Factions\command\subcommands\faction\AuditCommand;
use Jorgebyte\Factions\command\subcommands\faction\ChatCommand;
use Jorgebyte\Factions\command\subcommands\faction\CreateCommand;
use Jorgebyte\Factions\command\subcommands\faction\DisbandCommand;
use Jorgebyte\Factions\command\subcommands\faction\InfoCommand;
use Jorgebyte\Factions\command\subcommands\faction\PermCommand;
use Jorgebyte\Factions\command\subcommands\faction\TopCommand;
use Jorgebyte\Factions\command\subcommands\home\DelHomeCommand;
use Jorgebyte\Factions\command\subcommands\home\HomeCommand;
use Jorgebyte\Factions\command\subcommands\home\SetHomeCommand;
use Jorgebyte\Factions\command\subcommands\member\AcceptCommand;
use Jorgebyte\Factions\command\subcommands\member\DemoteCommand;
use Jorgebyte\Factions\command\subcommands\member\DenyCommand;
use Jorgebyte\Factions\command\subcommands\member\InviteCommand;
use Jorgebyte\Factions\command\subcommands\member\KickCommand;
use Jorgebyte\Factions\command\subcommands\member\LeaderCommand;
use Jorgebyte\Factions\command\subcommands\member\LeaveCommand;
use Jorgebyte\Factions\command\subcommands\member\PromoteCommand;
use Jorgebyte\Factions\command\subcommands\relation\AllyCommand;
use Jorgebyte\Factions\command\subcommands\relation\NeutralCommand;
use Jorgebyte\Factions\command\subcommands\territory\ChunkCommand;
use Jorgebyte\Factions\command\subcommands\territory\ClaimCommand;
use Jorgebyte\Factions\command\subcommands\territory\MapCommand;
use Jorgebyte\Factions\command\subcommands\territory\SubclaimCommand;
use Jorgebyte\Factions\command\subcommands\territory\UnclaimCommand;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\Main;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;

final class FactionsCommand extends BaseCommand
{
    public function __construct(
        protected readonly Main $plugin,
        private readonly CommandServiceRegistry $services,
    ) {
        parent::__construct($plugin, "factions", "Faction Command", ["f", "fac"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(Lang::t($sender, LangKeys::FACTIONS_COMMAND_USAGE->value));
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));

        $this->registerSubCommand(new CreateCommand($this->plugin->factionManager));
        $this->registerSubCommand(new InfoCommand($this->plugin->factionManager));
        $this->registerSubCommand(new DisbandCommand($this->plugin->factionManager, $this->plugin->sessionManager));
        $this->registerSubCommand(new InviteCommand($this->services->inviteActionService));
        $this->registerSubCommand(new AcceptCommand($this->services->inviteActionService));
        $this->registerSubCommand(new DenyCommand($this->services->inviteActionService));
        $this->registerSubCommand(new LeaveCommand($this->plugin->factionManager, $this->plugin->sessionManager));
        $this->registerSubCommand(new KickCommand($this->plugin->factionManager));
        $this->registerSubCommand(new PromoteCommand($this->plugin->factionManager));
        $this->registerSubCommand(new MoneyCommand($this->plugin->factionManager));
        $this->registerSubCommand(new DepositCommand($this->plugin->factionManager));
        $this->registerSubCommand(new WithdrawCommand($this->plugin->factionManager));
        $this->registerSubCommand(new ClaimCommand($this->plugin->factionManager, $this->services->territoryActionService));
        $this->registerSubCommand(new UnclaimCommand($this->plugin->factionManager, $this->services->territoryActionService));
        $this->registerSubCommand(new SetHomeCommand($this->plugin->factionManager));
        $this->registerSubCommand(new DelHomeCommand($this->plugin->factionManager));
        $this->registerSubCommand(new HomeCommand($this->plugin->factionManager));
        $this->registerSubCommand(new MapCommand($this->plugin->factionManager, $this->plugin->claimManager, $this->plugin->allyManager));
        $this->registerSubCommand(new ChunkCommand($this->services->chunkVisualizationService));
        $this->registerSubCommand(new TopCommand($this->plugin->factionManager));
        $this->registerSubCommand(new ChatCommand($this->plugin->sessionManager, $this->plugin->factionManager));
        $this->registerSubCommand(new AllyCommand($this->services->allyActionService));
        $this->registerSubCommand(new DemoteCommand($this->plugin->factionManager));
        $this->registerSubCommand(new LeaderCommand($this->plugin->factionManager));
        $this->registerSubCommand(new NeutralCommand($this->services->allyActionService));
        $this->registerSubCommand(new CacheCommand($this->plugin));
        $this->registerSubCommand(new SetPowerCommand($this->services->factionPowerActionService, $this->plugin->factionManager));
        $this->registerSubCommand(new AddPowerCommand($this->services->factionPowerActionService, $this->plugin->factionManager));
        $this->registerSubCommand(new RemovePowerCommand($this->services->factionPowerActionService, $this->plugin->factionManager));
        $this->registerSubCommand(new FreezePowerCommand($this->services->factionPowerActionService, $this->plugin->factionManager));
        $this->registerSubCommand(new UnfreezePowerCommand($this->services->factionPowerActionService, $this->plugin->factionManager));
        $this->registerSubCommand(new PermCommand($this->plugin->factionManager, $this->services->connector, $this->services->auditService));
        $this->registerSubCommand(new SubclaimCommand($this->plugin->factionManager, $this->plugin->claimManager, $this->services->subclaimService));
        $this->registerSubCommand(new AuditCommand($this->plugin->factionManager, $this->services->auditService));
    }
}
