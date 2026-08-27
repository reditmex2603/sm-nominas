<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nuevo rol "Conductor base": recibe sueldo diario (como Base) + pago de sus rutas de
     * transporte (Como Conductor). Registra Bodega y Transporte, pero NO eventos.
     */
    public function up(): void
    {
        $tipos = ['COLABORADOR BASE', 'FREELANCE', 'CONDUCTOR', 'CONDUCTOR BASE'];

        $this->cambiarEnum('colaboradores', 'tipo', $tipos);
        $this->cambiarEnum('historico_nomina', 'tipo_colaborador', $tipos);
    }

    public function down(): void
    {
        $tipos = ['COLABORADOR BASE', 'FREELANCE', 'CONDUCTOR'];

        $this->cambiarEnum('colaboradores', 'tipo', $tipos);
        $this->cambiarEnum('historico_nomina', 'tipo_colaborador', $tipos);
    }

    /**
     * MySQL/MariaDB: MODIFY COLUMN in-place (sin reconstruir la tabla). SQLite no lo
     * soporta: el schema builder redefine el enum reconstruyendo la tabla con la nueva
     * restricción CHECK (solo ocurre en bases de prueba, vacías al migrar).
     *
     * @param  array<int, string>  $valores
     */
    private function cambiarEnum(string $tabla, string $columna, array $valores): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table($tabla, function (Blueprint $table) use ($columna, $valores) {
                $table->enum($columna, $valores)->change();
            });

            return;
        }

        $lista = implode(',', array_map(fn ($v) => "'{$v}'", $valores));
        DB::statement("ALTER TABLE {$tabla} MODIFY COLUMN {$columna} ENUM({$lista}) NOT NULL");
    }
};
