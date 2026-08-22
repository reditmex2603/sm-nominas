<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_nomina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->enum('tipo_colaborador', ['COLABORADOR BASE', 'FREELANCE', 'CONDUCTOR']);
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
            $table->decimal('dias', 5, 2)->nullable();
            $table->decimal('sueldo_diario', 10, 2)->nullable();
            $table->decimal('total_base', 10, 2)->default(0);
            $table->decimal('bonos_evento', 10, 2)->default(0);
            $table->decimal('compensaciones', 10, 2)->default(0);
            $table->decimal('anticipos', 10, 2)->default(0);
            $table->decimal('total_final', 10, 2)->default(0);
            $table->enum('estado', ['PENDIENTE', 'PAGADO'])->default('PENDIENTE');
            $table->foreignId('evento_id')->nullable()->constrained('eventos')->nullOnDelete();
            $table->timestamp('fecha_calculo')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_nomina');
    }
};
