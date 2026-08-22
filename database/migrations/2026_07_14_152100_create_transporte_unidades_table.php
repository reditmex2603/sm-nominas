<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unidades de transporte: inventario de vehículos físicos de la flotilla (marca, modelo,
     * placas, documentos), distinto de `transportes_vehiculos` (categorías usadas solo para la
     * matriz de tarifas, ej. "Camioneta 3.5T"). `transporte_vehiculo_id` es opcional: vincula la
     * unidad física a su categoría de tarifa cuando aplica.
     */
    public function up(): void
    {
        Schema::create('transporte_unidades', function (Blueprint $table) {
            $table->id();
            $table->string('marca');
            $table->string('modelo');
            $table->string('numero_placas')->nullable();
            $table->enum('pertenencia', ['PROPIA', 'RENTADA']);
            $table->foreignId('transporte_vehiculo_id')->nullable()
                ->constrained('transportes_vehiculos')->nullOnDelete();

            // Documentos + datos de póliza (mismo patrón que colaborador_perfiles)
            $table->string('placas_documento_path')->nullable();
            $table->string('tarjeta_circulacion_documento_path')->nullable();
            $table->string('poliza_seguro_documento_path')->nullable();
            $table->string('numero_poliza_seguro')->nullable();
            $table->date('vigencia_poliza_seguro')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_unidades');
    }
};
