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

namespace Jorgebyte\Factions\integration\rank;

use IvanCraft623\RankSystem\session\Session;
use IvanCraft623\RankSystem\tag\Tag;
use IvanCraft623\RankSystem\tag\TagManager;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;

final class RankSystemTagBridge
{
    public static function registerFactionTags(FactionManager $factionManager): void
    {
        $tagManager = TagManager::getInstance();

        $tagManager->registerTag(new Tag('factions_name', static function (Session $session) use ($factionManager): string {
            $player = $session->getPlayer();
            $faction = $factionManager->getPlayerFaction($player->getXuid());
            return $faction !== null ? $faction->name : Lang::t($player, LangKeys::GENERIC_NONE->value);
        }));

        $tagManager->registerTag(new Tag('factions_power', static function (Session $session) use ($factionManager): string {
            $player = $session->getPlayer();
            $faction = $factionManager->getPlayerFaction($player->getXuid());
            return (string) ($faction !== null ? $faction->power : 0);
        }));

        $tagManager->registerTag(new Tag('factions_claims', static function (Session $session) use ($factionManager): string {
            $player = $session->getPlayer();
            $faction = $factionManager->getPlayerFaction($player->getXuid());
            return (string) ($faction !== null ? $faction->getClaimsCount() : 0);
        }));

        $tagManager->registerTag(new Tag('factions_bank', static function (Session $session) use ($factionManager): string {
            $player = $session->getPlayer();
            $faction = $factionManager->getPlayerFaction($player->getXuid());
            return (string) ($faction !== null ? $faction->money : 0.0);
        }));

        $tagManager->registerTag(new Tag('factions_allies', static function (Session $session) use ($factionManager): string {
            $player = $session->getPlayer();
            $faction = $factionManager->getPlayerFaction($player->getXuid());
            return (string) ($faction !== null ? $faction->getAlliesCount() : 0);
        }));
    }
}
