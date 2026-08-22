<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Se llena al marcar una cuota como PAGADA, sea automáticamente (nómina marcada pagada) o
     * manualmente (pago directo registrado en el módulo Préstamos) — trazabilidad de cuándo se
     * cobró cada plazo.
     */
    public function up(): void
    {
        Schema::table('prestamo_cuotas', function (Blueprint $table) {
            $table->date('fecha_pago')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('prestamo_cuotas', function (Blueprint $table) {
            $table->dropColumn('fecha_pago');
        });
    }
};
