<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ]);

    // Define el nivel de PHP del proyecto (PHP 8.3 en runtime, 8.4 en CI).
    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    // Aplica reglas de mejora de código de PHP y Laravel. En dry-run no modifica nada:
    //   vendor/bin/rector process --dry-run
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        LaravelSetList::LARAVEL_130,
        PHPUnitSetList::PHPUNIT_110,
    ]);

    // Directorios y archivos generados que no deben tocarse.
    $rectorConfig->skip([
        __DIR__.'/database/migrations/2023_06_07_000001_create_pulse_tables.php',
        __DIR__.'/resources/views/vendor',
        __DIR__.'/lang/vendor',
    ]);
};
