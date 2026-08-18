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

namespace Jorgebyte\Factions\command\subcommands\economy;

use CortexPE\Commando\args\FloatArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use Jorgebyte\Factions\application\shared\FactionResultLangMapper;
use Jorgebyte\Factions\command\utils\FactionCommandTrait;
use Jorgebyte\Factions\lang\Lang;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\faction\FactionManager;
use Jorgebyte\Factions\utils\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class DepositCommand extends BaseSubCommand
{
    use FactionCommandTrait;

    public function __construct(private readonly FactionManager $factionManager)
    {
        parent::__construct("deposit", "Deposit money into faction bank", ["d"]);
        $this->setPermission(Permissions::FACTIONS_COMMAND_DEPOSIT->value);
    }

    public function getPermission(): string
    {
        return Permissions::FACTIONS_COMMAND_DEPOSIT->value;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $amount = (float) $args["amount"];

        if ($amount <= 0) {
            $sender->sendMessage(Lang::t($sender, LangKeys::BANK_DEPOSIT_POSITIVE->value));
            return;
        }

        /** @var Player $sender */
        $faction = $this->getFactionOrMessage($sender, $this->factionManager, LangKeys::GENERIC_NOT_IN_FACTION->value);
        if ($faction === null) {
            return;
        }

        $provider = $this->factionManager->getEconomyProvider();
        if ($provider === null) {
            $sender->sendMessage(Lang::t($sender, LangKeys::ECONOMY_DISABLED->value));
            return;
        }

        Await::f2c(function () use ($sender, $faction, $amount) {
            $balance = yield from $this->getBalanceAsync($sender);
            if ($balance === null) {
                $sender->sendMessage(Lang::t($sender, LangKeys::BANK_TRANSACTION_FAILED->value));
                return;
            }

            if ($balance < $amount) {
                $sender->sendMessage(Lang::t($sender, LangKeys::BANK_DEPOSIT_NOT_ENOUGH->value));
                return;
            }

            $paid = yield from $this->takeMoneyAsync($sender, $amount);
            if (!$paid) {
                $sender->sendMessage(Lang::t($sender, LangKeys::BANK_TRANSACTION_FAILED->value));
                return;
            }

            $response = yield from $this->factionManager->depositToBank($faction, $amount);
            if (!$response->isSuccess()) {
                yield from $this->giveMoneyAsync($sender, $amount);
                $messageKey = FactionResultLangMapper::forDeposit($response->result);
                $sender->sendMessage(Lang::t($sender, $messageKey->value));
                return;
            }

            $sender->sendMessage(Lang::t($sender, LangKeys::BANK_DEPOSIT_SUCCESS->value, ["{amount}" => number_format($amount, 2)]));
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new FloatArgument("amount"));
    }

    /** @return \Generator<mixed, mixed, mixed, float|null> */
    private function getBalanceAsync(Player $player): \Generator
    {
        $provider = $this->factionManager->getEconomyProvider();
        if ($provider === null) {
            return null;
        }

        return yield from Await::promise(function (\Closure $resolve) use ($provider, $player): void {
            $provider->getMoney($player, static function (float $money) use ($resolve): void {
                try {
                    $resolve($money);
                } catch (\Throwable) {
                }
            });
        });
    }

    /** @return \Generator<mixed, mixed, mixed, bool> */
    private function takeMoneyAsync(Player $player, float $amount): \Generator
    {
        $provider = $this->factionManager->getEconomyProvider();
        if ($provider === null) {
            return false;
        }

        return yield from Await::promise(function (\Closure $resolve) use ($provider, $player, $amount): void {
            $provider->takeMoney($player, $amount, static function (bool $success) use ($resolve): void {
                try {
                    $resolve($success);
                } catch (\Throwable) {
                }
            });
        });
    }

    /** @return \Generator<mixed, mixed, mixed, bool> */
    private function giveMoneyAsync(Player $player, float $amount): \Generator
    {
        $provider = $this->factionManager->getEconomyProvider();
        if ($provider === null) {
            return false;
        }

        return yield from Await::promise(function (\Closure $resolve) use ($provider, $player, $amount): void {
            $provider->giveMoney($player, $amount, static function (bool $success) use ($resolve): void {
                try {
                    $resolve($success);
                } catch (\Throwable) {
                }
            });
        });
    }
}
