<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Requisitos de personal para la cotización de nómina del evento: cuántos colaboradores
     * Base (por categoría y nivel) y Freelance se necesitan. Estructura:
     * {"base": {"<categoria>": {"1": cantidad, "2": cantidad}}, "freelance": cantidad}.
     */
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->json('requisitos_cotizacion')->nullable()->after('pago_por_evento_completo');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('requisitos_cotizacion');
        });
    }
};
