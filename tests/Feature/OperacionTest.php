<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/*
 * Verificaciones ligeras de la configuración operativa (no ejecutan backups reales,
 * que son costosos y dependen del entorno): comandos registrados y scheduler configurado.
 */

test('los comandos de backup están registrados', function () {
    $comandos = array_keys(Artisan::all());

    expect($comandos)->toContain('backup:run')
        ->and($comandos)->toContain('backup:clean')
        ->and($comandos)->toContain('backup:list');
});

test('el scheduler agenda los backups diarios y la limpieza', function () {
    $schedule = app(Schedule::class);

    $tareas = collect($schedule->events())->map(fn ($e) => $e->command)->implode("\n");

    expect($tareas)->toContain('backup:run --only-db')
        ->and($tareas)->toContain('backup:run --only-files')
        ->and($tareas)->toContain('backup:clean');
});

test('el canal de logs JSON está configurado', function () {
    expect(config('logging.channels.json'))->not->toBeNull()
        ->and(config('logging.channels.json.driver'))->toBe('daily');
});
