<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup diario de BD + storage (documentos/evidencias). Se ejecuta en producción
// vía el scheduler del contenedor (docker/scripts/scheduler.sh) o `php artisan schedule:work`.
// Notificaciones de éxito/fallo se envían al canal de notificación configurado en config/backup.php.
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('backup:run --only-files')
    ->dailyAt('02:30')
    ->withoutOverlapping();

// Limpia los respaldos antiguos según la política de retención de config/backup.php.
Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->withoutOverlapping();
