<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La CLABE se almacena cifrada (cast EncryptedOrDefault); el valor cifrado supera con
     * creces los 18 caracteres de una CLABE en texto plano. Amplía la columna creada
     * inicialmente como string(18).
     */
    public function up(): void
    {
        Schema::table('colaborador_datos_bancarios', function (Blueprint $table) {
            $table->string('clave_interbancaria')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('colaborador_datos_bancarios', function (Blueprint $table) {
            $table->string('clave_interbancaria', 18)->nullable()->change();
        });
    }
};
