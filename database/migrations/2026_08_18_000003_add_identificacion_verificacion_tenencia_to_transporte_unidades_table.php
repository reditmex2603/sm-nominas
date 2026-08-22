<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Propiedades adicionales de las unidades de transporte: identificación (alias y
     * número de serie), datos de verificación (foto del comprobante, vencimiento, tipo
     * y color de engomado) y el documento de tenencia.
     */
    public function up(): void
    {
        Schema::table('transporte_unidades', function (Blueprint $table) {
            // Identificación
            $table->string('alias')->nullable();
            $table->string('numero_serie')->nullable();

            // Verificación
            $table->date('vigencia_verificacion')->nullable();
            $table->string('tipo_engomado')->nullable();
            $table->string('color_engomado')->nullable();
            $table->string('verificacion_documento_path')->nullable();

            // Tenencia
            $table->string('tenencia_documento_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transporte_unidades', function (Blueprint $table) {
            $table->dropColumn([
                'alias',
                'numero_serie',
                'vigencia_verificacion',
                'tipo_engomado',
                'color_engomado',
                'verificacion_documento_path',
                'tenencia_documento_path',
            ]);
        });
    }
};
