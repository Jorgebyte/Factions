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

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\command\args\FactionNameArgument;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use Jorgebyte\Factions\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;
use Throwable;

final class InfoCommand extends BaseSubCommand
{
    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("info", "Obtain information about a faction");
        $this->setPermission(Permissions::FACTIONS_COMMAND_INFO->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_INFO->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $name = $args["name"] ?? null;

        Await::f2c(/**
         * @throws Throwable
         */ function () use ($sender, $name) {
            /** @var Player $sender */
            if ($name === null) {
                $faction = $this->factionManager->getPlayerFaction($sender->getXuid());
                if ($faction === null) {
                    $faction = yield from $this->factionManager->loadFactionByPlayerXuid($sender->getXuid());
                }
            } else {
                $faction = yield from $this->factionManager->loadFactionByName($name);
            }

            if ($faction === null) {
                $sender->sendMessage($name === null
                    ? Lang::t($sender, LangKeys::INFO_NO_FACTION->value)
                    : Lang::t($sender, LangKeys::INFO_FACTION_NOT_FOUND->value, ["{name}" => $name]));
                return;
            }

            $roles = [Role::LEADER->value => [], Role::COLEADER->value => [], Role::MEMBER->value => []];
            $onlineMembers = [];

            foreach ($faction->getMembers() as $member) {
                $isOnline = PlayerUtils::getPlayerByXuid($member->playerXuid) !== null;
                $statusPrefix = $isOnline
                    ? Lang::t($sender, LangKeys::INFO_MEMBER_ONLINE_PREFIX->value)
                    : Lang::t($sender, LangKeys::INFO_MEMBER_OFFLINE_PREFIX->value);
                $displayName = $statusPrefix . $member->getPlayerName();

                $roles[$member->getRole()->value][] = $displayName;
                if ($isOnline) {
                    $onlineMembers[] = $displayName;
                }
            }

            $formatList = fn (array $list) => empty($list)
                ? "§7" . Lang::t($sender, LangKeys::GENERIC_NONE->value)
                : implode(TextFormat::WHITE . ", ", $list);

            $home = $faction->getHome();
            $homeText = $home instanceof Position
                ? TextFormat::GRAY . $home->getWorld()->getFolderName() . TextFormat::WHITE . " (" .
                (int) round($home->getX()) . ", " .
                (int) round($home->getY()) . ", " .
                (int) round($home->getZ()) . ")"
                : TextFormat::GRAY . Lang::t($sender, LangKeys::GENERIC_NONE->value);

            $alliesNames = [];
            foreach ($faction->getAllies() as $allyId) {
                if (($ally = yield from $this->factionManager->loadFaction($allyId))) {
                    $alliesNames[] = $ally->name;
                }
            }

            $maxMembers = $this->factionManager->getFactionConfig()->getMaxMembersPerFaction();
            $onlineCount = count($onlineMembers);
            $memberCount = $faction->getMembersCount();

            $powerDisplay = (string) $faction->power;
            if ($faction->isPowerFrozen()) {
                $rem = $faction->getFreezeTimeRemaining();
                $timeStr = sprintf("%02d:%02d", (int) ($rem / 60), $rem % 60);
                $powerDisplay .= " " . Lang::t($sender, LangKeys::STATUS_FROZEN->value, ["time" => $timeStr]);
            } elseif ($faction->isRaidable()) {
                $powerDisplay .= " " . Lang::t($sender, LangKeys::STATUS_RAIDABLE->value);
            }

            $sender->sendMessage(Lang::t(
                $sender,
                LangKeys::GET_INFO->value,
                [
                    "{faction}" => $faction->name,
                    "{name}" => $faction->name,
                    "{leader}" => $roles[Role::LEADER->value][0] ?? "§7" . Lang::t($sender, LangKeys::GENERIC_NONE->value),
                    "{coleaders}" => $formatList($roles[Role::COLEADER->value]),
                    "{members}" => $formatList($roles[Role::MEMBER->value]),
                    "{online_members}" => $formatList($onlineMembers),
                    "{total_members}" => $memberCount,
                    "{max_members}" => $maxMembers,
                    "{member_usage}" => $memberCount . "/" . $maxMembers,
                    "{online_count}" => $onlineCount,
                    "{power}" => $powerDisplay,
                    "{money}" => number_format($faction->money, 2),
                    "{kills}" => $faction->getKills(),
                    "{claims}" => $faction->getClaimsCount(),
                    "{home}" => $homeText,
                    "{allies}" => $faction->getAlliesCount(),
                    "{allies_list}" => $formatList($alliesNames),
                    "{creation_date}" => date("d/m/Y H:i", $faction->creationDate),
                ],
            ));
        }
        );
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new FactionNameArgument("name", true));
    }
}
