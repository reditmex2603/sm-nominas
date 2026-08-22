<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Migra los documentos sensibles del disco público (legacy) al disco privado.
 *
 * Cuando los documentos se almacenaban en el disco "public" colgaban del
 * webroot en /storage sin autenticación. Este comando los mueve al disco
 * "documentos" (fuera del webroot) de forma idempotente, una única vez por
 * despliegue. Los registros de la BD ya guardan rutas relativas, que siguen
 * siendo válidas en ambos discos.
 */
class MigrarDocumentos extends Command
{
    protected $signature = 'archivos:migrar';

    protected $description = 'Mueve documentos sensibles del almacenamiento público al disco privado "documentos"';

    public function handle(): int
    {
        $carpetas = [
            'perfiles',
            'fotografias',
            'unidades-transporte',
            'unidades-flotilla',
            'evidencias',
        ];

        $origen = Storage::disk('public');
        $destino = Storage::disk('documentos');

        $movidos = 0;
        $omitidos = 0;

        foreach ($carpetas as $carpeta) {
            if (! $origen->exists($carpeta)) {
                continue;
            }

            foreach ($origen->allFiles($carpeta) as $archivo) {
                // Ya migrado en una ejecución previa.
                if ($destino->exists($archivo)) {
                    $omitidos++;

                    continue;
                }

                $this->info("  moviendo {$archivo}");
                $destino->put($archivo, (string) $origen->get($archivo));
                $origen->delete($archivo);
                $movidos++;
            }
        }

        $this->warn("Migrados: {$movidos} | Ya existentes/omitidos: {$omitidos}");

        // Nota de operación: los archivos de branding (logos) permanecen públicos
        // a propósito (son la marca del sistema, no datos personales).

        return $movidos > 0 || $omitidos > 0 ? self::SUCCESS : self::SUCCESS;
    }
}
