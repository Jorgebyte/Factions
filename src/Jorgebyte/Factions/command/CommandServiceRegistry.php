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

namespace Jorgebyte\Factions\command;

use Jorgebyte\Factions\application\admin\FactionPowerActionService;
use Jorgebyte\Factions\application\audit\FactionAuditService;
use Jorgebyte\Factions\application\member\InviteActionService;
use Jorgebyte\Factions\application\relation\AllyActionService;
use Jorgebyte\Factions\application\territory\ChunkVisualizationService;
use Jorgebyte\Factions\application\territory\SubclaimService;
use Jorgebyte\Factions\application\territory\TerritoryActionService;
use poggit\libasynql\DataConnector;

final readonly class CommandServiceRegistry
{
    public function __construct(
        public ChunkVisualizationService $chunkVisualizationService,
        public TerritoryActionService $territoryActionService,
        public AllyActionService $allyActionService,
        public InviteActionService $inviteActionService,
        public FactionPowerActionService $factionPowerActionService,
        public SubclaimService $subclaimService,
        public FactionAuditService $auditService,
        public DataConnector $connector,
    ) {
    }
}
