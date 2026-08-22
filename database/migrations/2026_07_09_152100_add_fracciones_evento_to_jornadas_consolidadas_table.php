<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jornadas_consolidadas', function (Blueprint $table) {
            // Cuando un día tiene 2+ eventos, cada uno se pondera individualmente
            // (COMPLETO/TRASLAPE_50/TRASLAPE_40) en vez de usar el tipo_pago único del día.
            // Formato: {"<evento_id>": "COMPLETO" | "TRASLAPE_50" | "TRASLAPE_40"}.
            $table->json('fracciones_evento')->nullable()->after('tipo_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jornadas_consolidadas', function (Blueprint $table) {
            $table->dropColumn('fracciones_evento');
        });
    }
};
