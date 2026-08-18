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

namespace Jorgebyte\Factions\command\subcommands\admin;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\Main;
use Jorgebyte\Factions\utils\ConfigDefaults;
use pocketmine\command\CommandSender;

final class CacheCommand extends BaseSubCommand
{
    public function __construct(private readonly Main $plugin)
    {
        parent::__construct('cache', 'Admin cache utilities');
        $this->setPermission('factions.command.cache');
    }

    public function getPermission(): string
    {
        return 'factions.command.cache';
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $factionCache = $this->plugin->factionManager->getFactionCache();
        $factionMetrics = $factionCache->getMetrics()->toArray();
        $inviteMetrics = $this->plugin->inviteManager->getCacheMetrics()->toArray();
        $warmup = ConfigDefaults::getEmptyWarmupMetrics();

        $sender->sendMessage(Lang::t($sender, LangKeys::CACHE_STATS_PANEL->value, [
            '{faction_cache_size}' => (string) $factionCache->getCount(),
            '{faction_hits}' => (string) $factionMetrics['hits'],
            '{faction_misses}' => (string) $factionMetrics['misses'],
            '{faction_evictions}' => (string) $factionMetrics['evictions'],
            '{invite_cache_size}' => (string) $this->plugin->inviteManager->getCacheSize(),
            '{invite_hits}' => (string) $inviteMetrics['hits'],
            '{invite_misses}' => (string) $inviteMetrics['misses'],
            '{invite_evictions}' => (string) $inviteMetrics['evictions'],
            '{inflight_factions}' => (string) $this->plugin->factionManager->getInFlightFactionLoadCount(),
            '{inflight_xuids}' => (string) $this->plugin->factionManager->getInFlightPlayerFactionLoadCount(),
            '{pending_writes}' => (string) $this->plugin->factionManager->getPendingWriteCount(),
            '{warmup_enabled}' => $warmup['enabled'] ? 'yes' : 'no',
            '{warmup_running}' => $warmup['running'] ? 'yes' : 'no',
            '{warmup_completed}' => (string) $warmup['completed'],
            '{warmup_total}' => (string) $warmup['total'],
            '{warmup_remaining}' => (string) $warmup['remaining'],
            '{warmup_batch}' => (string) $warmup['batch_size'],
            '{warmup_interval}' => (string) $warmup['interval_ticks'],
            '{warmup_prioritize_online}' => $warmup['prioritize_online'] ? 'yes' : 'no',
        ]));
    }

    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}
