<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un viático se puede registrar para un colaborador asignado al evento (`colaborador_id`) o
     * como gasto general del evento (sin colaborador, usando el nombre libre que ya existía).
     */
    public function up(): void
    {
        Schema::table('viaticos', function (Blueprint $table) {
            $table->foreignId('colaborador_id')->nullable()->after('evento_id')
                ->constrained('colaboradores')->nullOnDelete();
            $table->string('nombre')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('viaticos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('colaborador_id');
            $table->string('nombre')->nullable(false)->change();
        });
    }
};
