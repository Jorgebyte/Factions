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

namespace Jorgebyte\Factions;

use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use Generator;
use IvanCraft623\languages\Translator;
use Jorgebyte\Factions\api\FactionsAPI;
use Jorgebyte\Factions\application\admin\FactionPowerActionService;
use Jorgebyte\Factions\application\audit\FactionAuditService;
use Jorgebyte\Factions\application\chat\FactionChatService;
use Jorgebyte\Factions\application\combat\FriendlyFireService;
use Jorgebyte\Factions\application\member\InviteActionService;
use Jorgebyte\Factions\application\relation\AllyActionService;
use Jorgebyte\Factions\application\territory\ChunkVisualizationService;
use Jorgebyte\Factions\application\territory\ClaimAccessService;
use Jorgebyte\Factions\application\territory\ClaimDesyncMitigationService;
use Jorgebyte\Factions\application\territory\SubclaimService;
use Jorgebyte\Factions\application\territory\TerritoryActionService;
use Jorgebyte\Factions\cache\CachePolicyService;
use Jorgebyte\Factions\command\CommandServiceRegistry;
use Jorgebyte\Factions\command\FactionsCommand;
use Jorgebyte\Factions\integration\display\FactionDisplaySyncService;
use Jorgebyte\Factions\integration\rank\RankIntegrationManager;
use Jorgebyte\Factions\integration\rank\RankSystemTagBridge;
use Jorgebyte\Factions\integration\scorehud\ScoreHudListener as FactionsScoreHudListener;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\TranslatorLoader;
use Jorgebyte\Factions\listener\ChatListener;
use Jorgebyte\Factions\listener\ClaimListener;
use Jorgebyte\Factions\listener\CombatListener;
use Jorgebyte\Factions\listener\FactionListener;
use Jorgebyte\Factions\listener\HomeWarmupListener;
use Jorgebyte\Factions\listener\PlayerListener;
use Jorgebyte\Factions\managers\ally\AllyManager;
use Jorgebyte\Factions\managers\claim\ClaimManager;
use Jorgebyte\Factions\managers\database\DatabaseManager;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\invite\InviteManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\task\CacheCleanupTask;
use Jorgebyte\Factions\task\ChunkVisualizerTask;
use Jorgebyte\Factions\task\PowerRegenTask;
use Jorgebyte\Factions\utils\ConfigDefaults;
use Jorgebyte\Factions\utils\FactionConfig;
use JsonException;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class Main extends PluginBase
{
    public const DEFAULT_LANGUAGE = "en_US";

    public DatabaseManager $databaseManager;

    public FactionManager $factionManager;

    public ClaimManager $claimManager;

    public FactionConfig $factionConfig;

    public AllyManager $allyManager;

    public InviteManager $inviteManager;

    public SessionManager $sessionManager;

    public RankIntegrationManager $rankIntegrationManager;

    public FactionDisplaySyncService $displaySyncService;

    public FactionChatService $factionChatService;

    public ClaimAccessService $claimAccessService;

    public ClaimDesyncMitigationService $claimDesyncMitigationService;

    public FriendlyFireService $friendlyFireService;

    public FactionAuditService $auditService;

    public SubclaimService $subclaimService;

    public CommandServiceRegistry $commandServiceRegistry;

    public FactionsAPI $api;

    protected Translator $translator;

    private ?EconomyProvider $economyProvider = null;

    /**
     * @throws JsonException
     * @throws Throwable
     */
    protected function onEnable(): void
    {
        if (!$this->ensureDataFolderExists()) {
            return;
        }

        $this->saveDefaultConfig();
        foreach ($this->getResources() as $resourcePath => $resourceInfo) {
            if (str_starts_with($resourcePath, "languages/") && str_ends_with($resourcePath, ".ini")) {
                $this->saveResource($resourcePath, true);
            }
        }

        ConfigDefaults::applyDefaults($this);
        $this->getConfig()->reload();
        $this->factionConfig = new FactionConfig($this);

        try {
            $this->databaseManager = new DatabaseManager($this, (array) $this->getConfig()->get("database", []));
        } catch (Throwable $e) {
            $this->getLogger()->critical("Database setup failed: " . $e->getMessage());
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->translator = TranslatorLoader::loadFromFolder($this);
        Lang::init($this->translator);

        $cachePolicy = CachePolicyService::fromConfig(
            $this->factionConfig->getFactionCacheMaxEntries(),
            $this->factionConfig->getFactionCacheMemoryThresholdMb(),
            [
                'low' => $this->factionConfig->getFactionCacheLowTtlSeconds(),
                'medium' => $this->factionConfig->getFactionCacheMediumTtlSeconds(),
                'high' => $this->factionConfig->getFactionCacheHighTtlSeconds(),
            ],
            $this->factionConfig->getInviteCacheTtlOverrideSeconds()
        );

        $this->factionManager = new FactionManager($this->databaseManager->getConnector(), $this->factionConfig, $cachePolicy);
        $this->allyManager = new AllyManager($this->databaseManager->getConnector(), $this->factionConfig, $this->factionManager);
        $this->claimManager = new ClaimManager($this->databaseManager->getConnector(), $this->factionConfig, $this->factionManager);
        $this->factionManager->setClaimManager($this->claimManager);
        $this->inviteManager = new InviteManager($this->factionConfig, $cachePolicy);
        $this->sessionManager = new SessionManager();

        $this->claimAccessService = new ClaimAccessService($this->claimManager, $this->factionManager);
        $this->claimDesyncMitigationService = new ClaimDesyncMitigationService($this->factionConfig);
        $this->displaySyncService = new FactionDisplaySyncService($this->factionManager, $this->sessionManager);
        $this->factionManager->setDisplaySyncService($this->displaySyncService);
        $this->factionChatService = new FactionChatService($this->sessionManager, $this->factionManager);
        $this->friendlyFireService = new FriendlyFireService($this->factionManager, $this->allyManager, $this->factionConfig);

        $this->auditService = new FactionAuditService($this->databaseManager->getConnector());
        $this->subclaimService = new SubclaimService($this->databaseManager->getConnector(), $this->claimManager, $this->auditService);

        $this->commandServiceRegistry = new CommandServiceRegistry(
            new ChunkVisualizationService($this->sessionManager),
            new TerritoryActionService($this->claimManager),
            new AllyActionService($this->allyManager, $this->factionManager),
            new InviteActionService($this->factionManager, $this->inviteManager),
            new FactionPowerActionService($this->factionManager),
            $this->subclaimService,
            $this->auditService,
            $this->databaseManager->getConnector()
        );

        $this->api = new FactionsAPI($this->factionManager, $this->claimManager, $this->allyManager);

        $this->initEconomy();
        $this->initIntegrations();

        Await::f2c(function () {
            yield from $this->loadData();
        });
    }

    protected function onDisable(): void
    {
        if (isset($this->factionManager)) {
            foreach ($this->factionManager->getLoadedFactions() as $faction) {
                $this->factionManager->queueFactionSave($faction);
            }
            Await::f2c(function (): \Generator {
                yield from $this->factionManager->flushPendingWrites();
            });
        }
        if (isset($this->databaseManager)) {
            $this->databaseManager->close();
        }
    }

    private function initEconomy(): void
    {
        libPiggyEconomy::init();
        try {
            $this->economyProvider = libPiggyEconomy::getProvider($this->getConfig()->get("economy"));
            $this->factionManager->setEconomyProvider($this->economyProvider);
        } catch (Throwable $e) {
            $this->getLogger()->error("Economy initialization failed: " . $e->getMessage());
        }
    }

    private function initIntegrations(): void
    {
        $this->rankIntegrationManager = new RankIntegrationManager();
        $pm = $this->getServer()->getPluginManager();

        if (($rankSystem = $pm->getPlugin('RankSystem')) !== null && $rankSystem->isEnabled()) {
            RankSystemTagBridge::registerFactionTags($this->factionManager);
        }

        if (($scoreHud = $pm->getPlugin('ScoreHud')) !== null && $scoreHud->isEnabled()) {
            $pm->registerEvents(new FactionsScoreHudListener($this->factionManager), $this);
        }
    }

    /**
     * @throws Throwable
     */
    private function loadData(): Generator
    {
        try {
            yield from $this->databaseManager->init();
            yield from $this->allyManager->preloadAlliances();
            yield from $this->claimManager->preloadClaims();
        } catch (Throwable $e) {
            $this->getLogger()->critical("Initial data load failed: " . $e->getMessage());
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // Just load online players' factions for quick start
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            yield from $this->factionManager->loadFactionByPlayerXuid($player->getXuid());
        }

        $this->registerHandlersAndTasks();
    }

    /**
     * @throws HookAlreadyRegistered
     */
    private function registerHandlersAndTasks(): void
    {
        $pm = $this->getServer()->getPluginManager();
        $pm->registerEvents(new PlayerListener($this->sessionManager, $this->factionManager, $this->displaySyncService), $this);
        $pm->registerEvents(new ClaimListener($this->claimAccessService, $this->claimDesyncMitigationService, $this->factionManager), $this);
        $pm->registerEvents(new FactionListener($this->friendlyFireService), $this);
        $pm->registerEvents(new ChatListener($this->sessionManager, $this->factionChatService), $this);
        $pm->registerEvents(new CombatListener($this->factionManager, $this->factionConfig), $this);
        $pm->registerEvents(new HomeWarmupListener($this), $this);

        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        $this->getServer()->getCommandMap()->register("Factions", new FactionsCommand($this, $this->commandServiceRegistry));

        $this->getScheduler()->scheduleRepeatingTask(new CacheCleanupTask($this->factionManager, $this->claimManager, $this->inviteManager), 20 * 60);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            Await::f2c(function (): \Generator {
                yield from $this->factionManager->flushPendingWrites();
            });
        }), 20 * 30);
        $this->getScheduler()->scheduleRepeatingTask(new ChunkVisualizerTask($this->sessionManager, $this->claimManager, $this->factionManager), 10);
        $this->getScheduler()->scheduleRepeatingTask(new PowerRegenTask($this->factionManager, $this->factionConfig), 20 * 60 * $this->factionConfig->getPowerRegenIntervalMinutes());
    }

    private function ensureDataFolderExists(): bool
    {
        $dataFolder = $this->getDataFolder();
        if (!is_dir($dataFolder)) {
            return mkdir($dataFolder, 0777, true);
        }
        return true;
    }
}
