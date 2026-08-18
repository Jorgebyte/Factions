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

namespace Jorgebyte\Factions\integration\scorehud;

use Ifera\ScoreHud\event\TagsResolveEvent;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use pocketmine\event\Listener;

final readonly class ScoreHudListener implements Listener
{
    public function __construct(
        private FactionManager $factionManager,
    ) {
    }

    public function onTagsResolve(TagsResolveEvent $event): void
    {
        $tag = $event->getTag();
        $name = $tag->getName();

        if (!str_starts_with($name, 'factions.')) {
            return;
        }

        $player = $event->getPlayer();
        $faction = $this->factionManager->getPlayerFaction($player->getXuid());

        if ($faction === null) {
            $none = Lang::t($player, LangKeys::GENERIC_NONE->value);
            $tag->setValue(match ($name) {
                'factions.name' => $none,
                default => '0'
            });
            return;
        }

        $status = Lang::t($player, LangKeys::STATUS_NORMAL->value);
        if ($faction->isPowerFrozen()) {
            $rem = $faction->getFreezeTimeRemaining();
            $timeStr = sprintf("%02d:%02d", (int) ($rem / 60), $rem % 60);
            $status = Lang::t($player, LangKeys::STATUS_FROZEN->value, ["time" => $timeStr]);
        } elseif ($faction->isRaidable()) {
            $status = Lang::t($player, LangKeys::STATUS_RAIDABLE->value);
        }

        $maxPower = (string) ($faction->getMembersCount() * $this->factionManager->getFactionConfig()->getPowerPerMember());
        $freezeTime = sprintf("%02d:%02d", (int) ($faction->getFreezeTimeRemaining() / 60), $faction->getFreezeTimeRemaining() % 60);

        $tag->setValue(match ($name) {
            'factions.name' => $faction->name,
            'factions.power' => (string) $faction->power,
            'factions.max_power' => $maxPower,
            'factions.status' => $status,
            'factions.freeze_time' => $freezeTime,
            'factions.kills' => (string) $faction->getKills(),
            'factions.bank', 'factions.money' => (string) $faction->money,
            'factions.claims' => (string) $faction->getClaimsCount(),
            'factions.members' => (string) $faction->getMembersCount(),
            'factions.allies' => (string) $faction->getAlliesCount(),
            default => $tag->getValue(),
        });
    }
}
