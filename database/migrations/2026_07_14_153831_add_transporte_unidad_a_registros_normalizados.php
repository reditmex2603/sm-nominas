<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Al registrar Transporte, además de la categoría de vehículo (ya existente, usada para
     * detectar tarifa) el conductor puede indicar la unidad física específica de la flotilla
     * (marca/modelo/placas) que usó ese día. Opcional — no todos los registros la tendrán.
     */
    public function up(): void
    {
        Schema::table('registros_normalizados', function (Blueprint $table) {
            $table->foreignId('transporte_unidad_id')->nullable()->after('distancia')
                ->constrained('transporte_unidades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registros_normalizados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transporte_unidad_id');
        });
    }
};
