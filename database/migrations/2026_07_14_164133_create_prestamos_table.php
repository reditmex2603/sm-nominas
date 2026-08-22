<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Préstamos: como Anticipos pero con pago en plazos. El admin captura monto total, número
     * de plazos, periodicidad y fecha de inicio; el sistema genera el calendario de cuotas
     * (tabla `prestamo_cuotas`) automáticamente. Solo aplica a colaboradores Base/Conductor
     * (tienen período de nómina con fechas calendario; Freelance se paga por evento).
     */
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->decimal('monto_total', 10, 2);
            $table->unsignedSmallInteger('num_plazos');
            $table->enum('periodicidad', ['SEMANAL', 'QUINCENAL', 'MENSUAL']);
            $table->date('fecha_inicio');
            $table->string('concepto')->nullable();
            $table->string('autoriza')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
