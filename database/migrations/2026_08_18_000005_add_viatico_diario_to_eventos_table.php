<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            // Gasto diario único por evento; cada colaborador que recibe viático se marca
            // con un check por día y se carga a este mismo monto.
            $table->decimal('viatico_diario', 10, 2)->nullable()->after('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('viatico_diario');
        });
    }
};
