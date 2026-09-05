<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Responsable (colaborador asignado al evento) encargado del mismo. Se usa en la pestaña
     * "Asignación y cotización" y en la impresión a terceros.
     */
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->foreignId('responsable_colaborador_id')
                ->nullable()
                ->after('id')
                ->constrained('colaboradores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_colaborador_id');
        });
    }
};
