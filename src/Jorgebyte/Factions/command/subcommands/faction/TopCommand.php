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

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\command\args\TopTypeArgument;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use SOFe\AwaitGenerator\Await;

final class TopCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("top", "View top factions");
        $this->setPermission(Permissions::FACTIONS_COMMAND_TOP->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_TOP->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $type = (string) ($args["type"] ?? "power");
        $page = (int) ($args["page"] ?? 1);

        Await::f2c(function () use ($sender, $type, $page) {
            $totalPages = yield from $this->factionManager->getTotalTopPages($type);
            if ($page < 1 || ($totalPages > 0 && $page > $totalPages)) {
                if ($totalPages === 0) {
                    $sender->sendMessage(Lang::t($sender, LangKeys::TOP_EMPTY->value));
                    return;
                }
                $sender->sendMessage(Lang::t($sender, LangKeys::TOP_INVALID_PAGE->value));
                return;
            }

            $typeLabel = match ($type) {
                'kills' => Lang::t($sender, LangKeys::TOP_TYPE_KILLS->value),
                'money' => Lang::t($sender, LangKeys::TOP_TYPE_MONEY->value),
                default => Lang::t($sender, LangKeys::TOP_TYPE_POWER->value),
            };

            $sender->sendMessage(Lang::t($sender, LangKeys::TOP_HEADER->value, ["{type}" => $typeLabel, "{page}" => $page, "{total}" => $totalPages]));

            $rows = yield from $this->factionManager->getTopFactions($type, $page);
            $rank = ($page - 1) * 10 + 1;

            foreach ($rows as $row) {
                $name = $row['name'];
                $value = $row[$type];

                if ($type === 'money') {
                    $value = "$" . number_format((float) $value, 2);
                }

                $sender->sendMessage(Lang::t($sender, LangKeys::TOP_ENTRY->value, [
                    "{rank}" => $rank++,
                    "{faction}" => $name,
                    "{value}" => (string) $value,
                ]));
            }
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->registerArgument(0, new TopTypeArgument("type", true));
        $this->registerArgument(1, new IntegerArgument("page", true));
    }
}
