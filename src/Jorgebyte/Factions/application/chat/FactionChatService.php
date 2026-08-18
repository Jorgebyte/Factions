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

namespace Jorgebyte\Factions\application\chat;

use Jorgebyte\Factions\entities\enums\ChatMode;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\managers\session\SessionManager;
use Jorgebyte\Factions\utils\PlayerUtils;
use pocketmine\player\Player;
use pocketmine\Server;

final readonly class FactionChatService
{
    public function __construct(
        private SessionManager $sessionManager,
        private FactionManager $factionManager,
    ) {
    }

    public function dispatch(Player $player, string $message): ChatDispatchResult
    {
        $session = $this->sessionManager->getSession($player->getXuid());
        if ($session === null || $session->chatMode === ChatMode::PUBLIC) {
            return ChatDispatchResult::passthrough();
        }

        $mode = $session->chatMode;
        $faction = $this->factionManager->getPlayerFaction($player->getXuid());
        if ($faction === null) {
            return ChatDispatchResult::resetToPublic();
        }

        $formatKey = match ($mode) {
            ChatMode::FACTION => LangKeys::CHAT_FORMAT_FACTION,
            ChatMode::ALLY => LangKeys::CHAT_FORMAT_ALLY,
        };

        $senderName = $player->getName();
        foreach ($this->collectRecipients($faction, $mode) as $recipient) {
            $recipient->sendMessage(Lang::t($recipient, $formatKey->value, [
                '{sender}' => $senderName,
                '{message}' => $message,
                '{faction}' => $faction->name,
            ]));
        }

        $player->getServer()->getLogger()->info(Lang::tn(LangKeys::CHAT_CONSOLE_LOG->value, [
            '{mode}' => $mode->value,
            '{sender}' => $senderName,
            '{message}' => $message,
        ]));

        return ChatDispatchResult::handled();
    }

    /**
     * @return array<string, Player>
     */
    private function collectRecipients(Faction $faction, ChatMode $mode): array
    {
        $recipients = [];

        foreach ($faction->getMembers() as $member) {
            $online = $this->resolveOnlinePlayer($member->playerXuid, $member->getPlayerName());
            if ($online !== null) {
                $recipients[$online->getXuid()] = $online;
            }
        }

        if ($mode !== ChatMode::ALLY) {
            return $recipients;
        }

        foreach ($faction->getAllies() as $allyId) {
            $allyFaction = $this->factionManager->getFactionCache()->get($allyId);
            if ($allyFaction === null) {
                continue;
            }

            foreach ($allyFaction->getMembers() as $allyMember) {
                $online = $this->resolveOnlinePlayer($allyMember->playerXuid, $allyMember->getPlayerName());
                if ($online !== null) {
                    $recipients[$online->getXuid()] = $online;
                }
            }
        }

        return $recipients;
    }

    private function resolveOnlinePlayer(string $xuid, string $fallbackName): ?Player
    {
        $onlineByXuid = PlayerUtils::getPlayerByXuid($xuid);
        if ($onlineByXuid !== null && $onlineByXuid->isOnline()) {
            return $onlineByXuid;
        }

        $onlineByName = Server::getInstance()->getPlayerExact($fallbackName);
        if ($onlineByName !== null && $onlineByName->isOnline()) {
            return $onlineByName;
        }

        return null;
    }
}
