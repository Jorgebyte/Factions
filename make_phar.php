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

$fileName = "Factions.phar";

if (file_exists($fileName)) {
    unlink($fileName);
}

$phar = new Phar($fileName);
$phar->startBuffering();

$phar->setStub("<?php echo 'Factions Plugin'; __HALT_COMPILER();");

$phar->buildFromDirectory(__DIR__, '/^(?!(.*(\.git|\.idea|composer\.json|composer\.lock|make_phar\.php))).*$/i');

$phar->stopBuffering();

echo "Phar created: " . $fileName . PHP_EOL;
