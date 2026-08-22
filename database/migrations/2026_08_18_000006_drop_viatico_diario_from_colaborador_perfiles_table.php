<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El gasto diario ahora es único por evento (eventos.viatico_diario),
        // ya no por colaborador.
        Schema::table('colaborador_perfiles', function (Blueprint $table) {
            $table->dropColumn('viatico_diario');
        });
    }

    public function down(): void
    {
        Schema::table('colaborador_perfiles', function (Blueprint $table) {
            $table->decimal('viatico_diario', 10, 2)->nullable();
        });
    }
};
