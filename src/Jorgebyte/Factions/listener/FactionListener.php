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

namespace Jorgebyte\Factions\listener;

use Jorgebyte\Factions\application\combat\FriendlyFireService;
use Jorgebyte\Factions\lang\Lang;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;

final readonly class FactionListener implements Listener
{
    public function __construct(
        private FriendlyFireService $friendlyFireService,
    ) {
    }

    /**
     * @priority HIGH
     * @ignoreCanceled true
     */
    public function onEntityDamage(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        if ($damager === null) {
            return;
        }

        $decision = $this->friendlyFireService->evaluate($damager, $event->getEntity());
        if (!$decision->cancelDamage) {
            return;
        }

        $event->cancel();
        if ($decision->notifier !== null && $decision->messageKey !== null) {
            $decision->notifier->sendMessage(Lang::t($decision->notifier, $decision->messageKey->value));
        }
    }
}
