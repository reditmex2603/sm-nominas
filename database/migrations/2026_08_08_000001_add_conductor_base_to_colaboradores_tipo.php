<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Nuevo rol "Conductor base": recibe sueldo diario (como Base) + pago de sus rutas de
     * transporte (Como Conductor). Registra Bodega y Transporte, pero NO eventos.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE colaboradores MODIFY COLUMN tipo ENUM('COLABORADOR BASE','FREELANCE','CONDUCTOR','CONDUCTOR BASE') NOT NULL");
        DB::statement("ALTER TABLE historico_nomina MODIFY COLUMN tipo_colaborador ENUM('COLABORADOR BASE','FREELANCE','CONDUCTOR','CONDUCTOR BASE') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE colaboradores MODIFY COLUMN tipo ENUM('COLABORADOR BASE','FREELANCE','CONDUCTOR') NOT NULL");
        DB::statement("ALTER TABLE historico_nomina MODIFY COLUMN tipo_colaborador ENUM('COLABORADOR BASE','FREELANCE','CONDUCTOR') NOT NULL");
    }
};