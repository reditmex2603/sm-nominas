<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conductor (colaborador asignado al evento, no Freelance) responsable de manejar una
     * unidad de transporte durante el evento.
     */
    public function up(): void
    {
        Schema::table('evento_unidades', function (Blueprint $table) {
            $table->foreignId('conductor_colaborador_id')
                ->nullable()
                ->after('transporte_unidad_id')
                ->constrained('colaboradores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evento_unidades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conductor_colaborador_id');
        });
    }
};
