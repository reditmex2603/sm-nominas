<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada cuota queda PENDIENTE hasta que la nómina que la cubre se marca PAGADO
     * (`historico_nomina_id` se liga al guardar el cálculo, y `estado` pasa a PAGADA cuando esa
     * nómina se marca pagada) — evita re-descontar la misma cuota si se recalcula el período.
     */
    public function up(): void
    {
        Schema::create('prestamo_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_plazo');
            $table->decimal('monto', 10, 2);
            $table->date('fecha_programada');
            $table->enum('estado', ['PENDIENTE', 'PAGADA'])->default('PENDIENTE');
            $table->foreignId('historico_nomina_id')->nullable()
                ->constrained('historico_nomina')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_cuotas');
    }
};
