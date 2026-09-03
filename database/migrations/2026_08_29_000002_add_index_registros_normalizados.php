<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice compuesto para las consultas más frecuentes del cálculo de nómina:
     * NominaCalculator filtra registros por colaborador + tipo de actividad + rango de fechas
     * (ver CalculadorBase::calcular y AbstractCalculadorNomina::registrosDeEvento). Sin él,
     * MariaDB/MySQL solo usan el índice individual de la FK y recorren el resto de la tabla.
     */
    public function up(): void
    {
        Schema::table('registros_normalizados', function (Blueprint $table) {
            $table->index(['colaborador_id', 'tipo_actividad', 'fecha'], 'registros_col_actividad_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('registros_normalizados', function (Blueprint $table) {
            $table->dropIndex('registros_col_actividad_fecha');
        });
    }
};
