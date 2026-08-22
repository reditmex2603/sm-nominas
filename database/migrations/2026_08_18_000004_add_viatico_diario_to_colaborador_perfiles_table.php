<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gasto de viáticos por defecto (por día) de cada colaborador. Es una configuración
     * global por persona, ajustable desde la matriz de viáticos de un evento.
     */
    public function up(): void
    {
        Schema::table('colaborador_perfiles', function (Blueprint $table) {
            $table->decimal('viatico_diario', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colaborador_perfiles', function (Blueprint $table) {
            $table->dropColumn('viatico_diario');
        });
    }
};
