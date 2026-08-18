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

echo "Building Factions Phar...\n";

$pharName = "Factions.phar";
if (file_exists($pharName)) {
    unlink($pharName);
}

$start = microtime(true);
$phar = new Phar($pharName);

$phar->setSignatureAlgorithm(Phar::SHA1);

$phar->startBuffering();

$baseDir = __DIR__;

$addDir = function (string $dir) use ($phar, $baseDir) {
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        $path = $file->getPathname();
        $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);

        if (str_contains($relativePath, '.git') || str_contains($relativePath, '.idea')) {
            continue;
        }

        echo "Adding: {$relativePath}\n";
        $phar->addFile($path, str_replace('\\', '/', $relativePath));
    }
};

$addDir($baseDir . '/src');
$addDir($baseDir . '/resources');
$addDir($baseDir . '/vendor');

$phar->addFile($baseDir . '/plugin.yml', 'plugin.yml');
if (file_exists($baseDir . '/LICENSE')) {
    $phar->addFile($baseDir . '/LICENSE', 'LICENSE');
}
if (file_exists($baseDir . '/README.md')) {
    $phar->addFile($baseDir . '/README.md', 'README.md');
}

$stub = '<?php
if(file_exists(__DIR__ . "/vendor/autoload.php")){
    require __DIR__ . "/vendor/autoload.php";
}
__HALT_COMPILER();
';

$phar->setStub($stub);
$phar->stopBuffering();

$end = microtime(true);
$time = round($end - $start, 3);

echo "Phar created successfully: {$pharName} in {$time} seconds.\n";
