<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Copia los datos bancarios que hoy viven en colaborador_perfiles (registro único) a la
     * nueva tabla colaborador_datos_bancarios, como primer registro de cada colaborador.
     * La columna clave_interbancaria viaja cifrada tal cual está en la base de datos; el cast
     * EncryptedOrDefault del modelo la descifra al leer.
     */
    public function up(): void
    {
        $perfiles = DB::table('colaborador_perfiles')
            ->whereNotNull('banco')
            ->orWhereNotNull('beneficiario')
            ->orWhereNotNull('clave_interbancaria')
            ->get(['colaborador_id', 'banco', 'beneficiario', 'clave_interbancaria']);

        foreach ($perfiles as $perfil) {
            DB::table('colaborador_datos_bancarios')->insert([
                'colaborador_id' => $perfil->colaborador_id,
                'banco' => $perfil->banco,
                'beneficiario' => $perfil->beneficiario,
                'clave_interbancaria' => $perfil->clave_interbancaria,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('colaborador_datos_bancarios')->delete();
    }
};
