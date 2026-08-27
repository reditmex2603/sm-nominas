<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Cifra datos bancarios y personales existentes que aún estén en texto plano.
 * Idempotente: omite filas ya cifradas. Diseñado para ejecutarse en producción
 * inmediatamente después del despliegue que añade el cast EncryptedOrDefault.
 */
class CifrarDatosPerfiles extends Command
{
    protected $signature = 'perfiles:cifrar-datos';

    protected $description = 'Cifra clave_interbancaria y numero_seguro_social en ColaboradorPerfil (texto plano residual)';

    private const CAMPOS = ['clave_interbancaria', 'numero_seguro_social'];

    public function handle(): int
    {
        $actualizados = 0;
        $yaCifrados = 0;

        $rows = DB::table('colaborador_perfiles')
            ->where(function ($q) {
                foreach (self::CAMPOS as $campo) {
                    $q->orWhereNotNull($campo);
                }
            })
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $updates = [];

            foreach (self::CAMPOS as $campo) {
                $valor = $row->$campo;

                if ($valor === null) {
                    continue;
                }

                try {
                    Crypt::decryptString($valor);
                    $yaCifrados++;
                } catch (DecryptException) {
                    $updates[$campo] = Crypt::encryptString($valor);
                }
            }

            if ($updates !== []) {
                DB::table('colaborador_perfiles')->where('id', $row->id)->update($updates);
                $actualizados++;
            }
        }

        $this->line("Perfiles procesados: {$rows->count()}");
        $this->line("  - actualizados (texto plano → cifrado): {$actualizados}");
        $this->line("  - ya cifrados (omitidos):               {$yaCifrados}");

        if ($actualizados > 0 && app()->isProduction()) {
            $this->warn('Respaldos: los datos ya cifrados solo pueden descifrarse con la misma APP_KEY del .env de producción. Asegúrate de respaldar APP_KEY.');
        }

        return self::SUCCESS;
    }
}
