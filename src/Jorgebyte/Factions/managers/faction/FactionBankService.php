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

namespace Jorgebyte\Factions\managers\faction;

use Generator;
use Jorgebyte\Factions\application\audit\FactionAuditService;
use Jorgebyte\Factions\entities\Faction;
use pocketmine\player\Player;
use Throwable;

/**
 * Professional bank service for faction economy operations
 * Centralized handler for all money-related transactions
 */
final readonly class FactionBankService
{
    /**
     * Operation type enum-like constants for better semantics
     */
    private const OPERATION_DEPOSIT = 'deposit';
    private const OPERATION_WITHDRAW = 'withdraw';

    public function __construct(
        private FactionManager $factionManager,
        private ?FactionAuditService $auditService = null
    ) {
    }

    /**
     * Deposit money into faction bank
     *
     * @return Generator<mixed, mixed, mixed, FactionResponse>
     */
    public function deposit(Faction $faction, float $amount, ?Player $actor = null): Generator
    {
        $response = $this->processTransaction($faction, $amount, self::OPERATION_DEPOSIT);
        if ($response->isSuccess() && $this->auditService !== null && $actor !== null) {
            yield from $this->auditService->log(
                $faction,
                $actor->getXuid(),
                $actor->getName(),
                "BANK_DEPOSIT",
                "Deposited $" . number_format($amount, 2)
            );
        }
        return $response;
    }

    /**
     * Withdraw money from faction bank
     *
     * @return Generator<mixed, mixed, mixed, FactionResponse>
     */
    public function withdraw(Faction $faction, float $amount, ?Player $actor = null): Generator
    {
        $response = $this->processTransaction($faction, $amount, self::OPERATION_WITHDRAW);
        if ($response->isSuccess() && $this->auditService !== null && $actor !== null) {
            yield from $this->auditService->log(
                $faction,
                $actor->getXuid(),
                $actor->getName(),
                "BANK_WITHDRAW",
                "Withdrew $" . number_format($amount, 2)
            );
        }
        return $response;
    }

    /**
     * Core transaction processor
     * Handles both deposit and withdraw operations with unified logic
     *
     * @param Faction $faction Target faction
     * @param float $amount Money amount to process
     * @param string $operation Operation type (deposit|withdraw)
     * @return FactionResponse
     */
    private function processTransaction(Faction $faction, float $amount, string $operation): FactionResponse
    {
        if (!$this->isValidAmount($amount)) {
            return FactionResponse::fail(FactionResult::INVALID_AMOUNT);
        }

        if ($operation === self::OPERATION_WITHDRAW && !$faction->hasEnoughMoney($amount)) {
            return FactionResponse::fail(FactionResult::INSUFFICIENT_FACTION_FUNDS, [
                'required' => $amount,
                'available' => $faction->money,
            ]);
        }

        try {
            $this->applyTransaction($faction, $amount, $operation);

            $this->factionManager->updateFactionMoney($faction);

            return FactionResponse::success([
                'amount' => $amount,
                'faction_id' => $faction->id,
                'operation' => $operation,
                'new_balance' => $faction->money,
            ]);
        } catch (Throwable) {
            return FactionResponse::fail(FactionResult::INTERNAL_ERROR);
        }
    }

    /**
     * Apply transaction to faction money
     *
     * @param Faction $faction Faction to modify
     * @param float $amount Transaction amount
     * @param string $operation Operation type
     */
    private function applyTransaction(Faction $faction, float $amount, string $operation): void
    {
        match ($operation) {
            self::OPERATION_DEPOSIT => $faction->addMoney($amount),
            self::OPERATION_WITHDRAW => $faction->removeMoney($amount),
            default => throw new \InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    /**
     * Validate transaction amount
     *
     * @param float $amount Amount to validate
     * @return bool True if valid
     */
    private function isValidAmount(float $amount): bool
    {
        return $amount > 0 && is_finite($amount) && !is_nan($amount);
    }
}
