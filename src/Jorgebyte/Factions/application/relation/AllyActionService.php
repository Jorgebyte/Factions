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

namespace Jorgebyte\Factions\application\relation;

use Generator;
use Jorgebyte\Factions\application\shared\CommandResult;
use Jorgebyte\Factions\entities\enums\Role;
use Jorgebyte\Factions\entities\Faction;
use Jorgebyte\Factions\lang\LangKeys;
use Jorgebyte\Factions\managers\ally\AcceptAllyRequest;
use Jorgebyte\Factions\managers\ally\AllyManager;
use Jorgebyte\Factions\managers\ally\AllyResult;
use Jorgebyte\Factions\managers\ally\RemoveAllyRequest;
use Jorgebyte\Factions\managers\ally\SendAllyRequest;
use Jorgebyte\Factions\managers\faction\FactionManager;
use pocketmine\player\Player;

final readonly class AllyActionService
{
    public function __construct(
        private AllyManager $allyManager,
        private FactionManager $factionManager,
    ) {
    }

    /** @return Generator<mixed, mixed, mixed, AllyPrecheckResult> */
    public function precheck(Player $sender, string $targetName): Generator
    {
        $myFaction = $this->factionManager->getPlayerFaction($sender->getXuid());
        if ($myFaction === null) {
            return AllyPrecheckResult::fail(new CommandResult(LangKeys::RELATION_NOT_IN_FACTION));
        }

        $targetFaction = yield from $this->factionManager->loadFactionByName($targetName);
        if ($targetFaction === null) {
            return AllyPrecheckResult::fail(new CommandResult(LangKeys::RELATION_TARGET_NOT_FOUND, ['{faction}' => $targetName]));
        }

        if ($myFaction->id === $targetFaction->id) {
            return AllyPrecheckResult::fail(new CommandResult(LangKeys::RELATION_CANNOT_RELATION_SELF));
        }

        $member = $myFaction->getMember($sender->getXuid());
        if ($member === null) {
            return AllyPrecheckResult::fail(new CommandResult(LangKeys::RELATION_NO_PERMISSION));
        }

        return AllyPrecheckResult::success($myFaction, $targetFaction, $member->getRole());
    }

    /**
     * @throws \Throwable
     * @return Generator<mixed, mixed, mixed, CommandResult>
     */
    public function accept(Faction $myFaction, Faction $targetFaction, Role $role): Generator
    {
        $response = yield from $this->allyManager->acceptAllyRequest(new AcceptAllyRequest(
            $targetFaction->id,
            $myFaction->id,
            $role,
        ));

        if ($response->result === AllyResult::SUCCESS) {
            return new CommandResult(LangKeys::RELATION_ALLY_ACCEPTED_SELF, ['{faction}' => $targetFaction->name]);
        }

        $messageKey = AllyResultLangMapper::forAccept($response->result);

        return new CommandResult($messageKey);
    }

    /**
     * @throws \Throwable
     * @return Generator<mixed, mixed, mixed, CommandResult>
     */
    public function deny(Faction $myFaction, Faction $targetFaction): Generator
    {
        $response = yield from $this->allyManager->removeAlly(new RemoveAllyRequest($targetFaction->id, $myFaction->id));
        if ($response->result === AllyResult::SUCCESS) {
            return new CommandResult(LangKeys::DENY_SUCCESS);
        }

        $messageKey = AllyResultLangMapper::forDeny($response->result);

        return new CommandResult($messageKey);
    }

    /** @return Generator<mixed, mixed, mixed, CommandResult> */
    public function neutral(Faction $myFaction, Faction $targetFaction): Generator
    {
        $response = yield from $this->allyManager->removeAlly(new RemoveAllyRequest($myFaction->id, $targetFaction->id));
        if ($response->isSuccess()) {
            return new CommandResult(LangKeys::RELATION_NEUTRAL_SET_SELF, ['{faction}' => $targetFaction->name]);
        }

        return new CommandResult(AllyResultLangMapper::forNeutral($response->result));
    }

    /** @return Generator<mixed, mixed, mixed, CommandResult> */
    public function requestOrAutoAccept(Faction $myFaction, Faction $targetFaction, Role $role): Generator
    {
        if ($myFaction->isAlly($targetFaction->id)) {
            return new CommandResult(LangKeys::RELATION_ALREADY_RELATION);
        }

        $accepted = yield from $this->allyManager->acceptAllyRequest(new AcceptAllyRequest(
            $targetFaction->id,
            $myFaction->id,
            $role,
        ));
        if ($accepted->result === AllyResult::SUCCESS) {
            return new CommandResult(LangKeys::RELATION_ALLY_ACCEPTED_SELF, ['{faction}' => $targetFaction->name]);
        }

        $sent = yield from $this->allyManager->sendAllyRequest(new SendAllyRequest(
            $myFaction->id,
            $targetFaction->id,
            $role,
        ));

        if ($sent->result === AllyResult::SUCCESS) {
            return new CommandResult(LangKeys::RELATION_ALLY_REQUEST_SENT, ['{faction}' => $targetFaction->name]);
        }

        $messageKey = AllyResultLangMapper::forRequest($sent->result);

        return new CommandResult($messageKey, ['{faction}' => $targetFaction->name]);
    }
}
