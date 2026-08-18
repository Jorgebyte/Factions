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

namespace Jorgebyte\Factions\integration\display;

use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use IvanCraft623\RankSystem\session\SessionManager as RankSessionManager;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\utils\PlayerUtils;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

final class FactionDisplaySyncService
{
    private bool $scoreHudAvailable;

    private bool $rankSystemAvailable;

    public function __construct(
        private readonly FactionManager $factionManager,
        private readonly SessionManager $sessionManager,
    ) {
        $pm = Server::getInstance()->getPluginManager();
        $scoreHud = $pm->getPlugin('ScoreHud');
        $rankSystem = $pm->getPlugin('RankSystem');
        $this->scoreHudAvailable = $scoreHud instanceof Plugin && $scoreHud->isEnabled();
        $this->rankSystemAvailable = $rankSystem instanceof Plugin && $rankSystem->isEnabled();
    }

    public function syncPlayerState(Player $player): void
    {
        if ($this->scoreHudAvailable) {
            ((new PlayerTagsUpdateEvent($player, $this->buildTags($player))))->call();
        }

        if ($this->rankSystemAvailable) {
            RankSessionManager::getInstance()->get($player)?->updateNameTag();
        }
    }

    public function syncFactionPlayers(Faction $faction): void
    {
        foreach ($faction->getOnlineMembers() as $player) {
            $this->syncPlayerState($player);
        }
    }

    public function clearFactionPlayers(Faction $faction): void
    {
        foreach ($faction->getMembers() as $member) {
            $this->sessionManager->resetChatContext($member->playerXuid);

            $online = PlayerUtils::getPlayerByXuid($member->playerXuid)
                ?? Server::getInstance()->getPlayerExact($member->getPlayerName());

            if ($online instanceof Player) {
                $this->syncPlayerState($online);
            }
        }
    }

    public function clearPlayerStateByXuid(string $xuid): void
    {
        $this->sessionManager->resetChatContext($xuid);

        $player = PlayerUtils::getPlayerByXuid($xuid);

        if ($player instanceof Player) {
            $this->syncPlayerState($player);
        }
    }

    /**
     * @return ScoreTag[]
     */
    private function buildTags(Player $player): array
    {
        $faction = $this->factionManager->getPlayerFaction($player->getXuid());

        if ($faction === null) {
            return [
                new ScoreTag('factions.name', Lang::t($player, LangKeys::GENERIC_NONE->value)),
                new ScoreTag('factions.power', '0'),
                new ScoreTag('factions.kills', '0'),
                new ScoreTag('factions.bank', '0'),
                new ScoreTag('factions.money', '0'),
                new ScoreTag('factions.claims', '0'),
                new ScoreTag('factions.members', '0'),
                new ScoreTag('factions.allies', '0'),
            ];
        }

        return [
            new ScoreTag('factions.name', $faction->name),
            new ScoreTag('factions.power', (string) $faction->power),
            new ScoreTag('factions.kills', (string) $faction->getKills()),
            new ScoreTag('factions.bank', (string) $faction->money),
            new ScoreTag('factions.money', (string) $faction->money),
            new ScoreTag('factions.claims', (string) $faction->getClaimsCount()),
            new ScoreTag('factions.members', (string) $faction->getMembersCount()),
            new ScoreTag('factions.allies', (string) $faction->getAlliesCount()),
        ];
    }
}
