<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El traslape ya no es un 40%/50% fijo (TRASLAPE_40/TRASLAPE_50 en el enum tipo_pago):
     * se colapsa a un solo valor 'TRASLAPE' y el porcentaje real lo captura el admin en la
     * nueva columna `traslape_pct` (1-99). `fracciones_evento` (días con 2+ eventos) pasa del
     * mismo modo de 'COMPLETO'/'TRASLAPE_50'/'TRASLAPE_40' a guardar el porcentaje entero
     * directamente (100 = completo).
     */
    public function up(): void
    {
        // 1. Ampliar el enum temporalmente: conserva los valores viejos mientras se migran los datos.
        $this->alterarTipoPago([
            'JORNADA_COMPLETA', 'JORNADA_COMPLETA + EVENTO', 'TRASLAPE_40', 'TRASLAPE_50',
            'TRASLAPE', 'SIN_PAGO', 'ERROR_EVENTO',
        ]);

        Schema::table('jornadas_consolidadas', function (Blueprint $table) {
            $table->unsignedTinyInteger('traslape_pct')->nullable()->after('tipo_pago');
        });

        DB::table('jornadas_consolidadas')->where('tipo_pago', 'TRASLAPE_40')
            ->update(['tipo_pago' => 'TRASLAPE', 'traslape_pct' => 40]);
        DB::table('jornadas_consolidadas')->where('tipo_pago', 'TRASLAPE_50')
            ->update(['tipo_pago' => 'TRASLAPE', 'traslape_pct' => 50]);

        $this->migrarFraccionesEvento(fn ($valor) => match ($valor) {
            'COMPLETO' => 100,
            'TRASLAPE_50' => 50,
            'TRASLAPE_40' => 40,
            default => is_numeric($valor) ? (int) $valor : 100,
        });

        // 2. Angostar el enum a la lista final, ya sin TRASLAPE_40/TRASLAPE_50.
        $this->alterarTipoPago([
            'JORNADA_COMPLETA', 'JORNADA_COMPLETA + EVENTO', 'TRASLAPE', 'SIN_PAGO', 'ERROR_EVENTO',
        ]);
    }

    public function down(): void
    {
        $this->alterarTipoPago([
            'JORNADA_COMPLETA', 'JORNADA_COMPLETA + EVENTO', 'TRASLAPE_40', 'TRASLAPE_50',
            'TRASLAPE', 'SIN_PAGO', 'ERROR_EVENTO',
        ]);

        DB::table('jornadas_consolidadas')->where('tipo_pago', 'TRASLAPE')
            ->where('traslape_pct', '<=', 44)->update(['tipo_pago' => 'TRASLAPE_40']);
        DB::table('jornadas_consolidadas')->where('tipo_pago', 'TRASLAPE')
            ->where('traslape_pct', '>', 44)->update(['tipo_pago' => 'TRASLAPE_50']);

        $this->migrarFraccionesEvento(fn ($pct) => $pct >= 100 ? 'COMPLETO' : ($pct <= 44 ? 'TRASLAPE_40' : 'TRASLAPE_50'));

        $this->alterarTipoPago([
            'JORNADA_COMPLETA', 'JORNADA_COMPLETA + EVENTO', 'TRASLAPE_40', 'TRASLAPE_50',
            'SIN_PAGO', 'ERROR_EVENTO',
        ]);

        Schema::table('jornadas_consolidadas', function (Blueprint $table) {
            $table->dropColumn('traslape_pct');
        });
    }

    /** @param  array<int, string>  $valores */
    private function alterarTipoPago(array $valores): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('jornadas_consolidadas', function (Blueprint $table) use ($valores) {
                $table->enum('tipo_pago', $valores)->default('SIN_PAGO')->change();
            });

            return;
        }

        $lista = implode(',', array_map(fn ($v) => "'{$v}'", $valores));
        DB::statement("ALTER TABLE jornadas_consolidadas MODIFY COLUMN tipo_pago ENUM({$lista}) NOT NULL DEFAULT 'SIN_PAGO'");
    }

    private function migrarFraccionesEvento(Closure $convertir): void
    {
        DB::table('jornadas_consolidadas')
            ->whereNotNull('fracciones_evento')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($convertir) {
                foreach ($rows as $row) {
                    $fracciones = json_decode($row->fracciones_evento, true);

                    if (! is_array($fracciones) || empty($fracciones)) {
                        continue;
                    }

                    $nuevo = [];
                    foreach ($fracciones as $eventoId => $valor) {
                        $nuevo[$eventoId] = $convertir($valor);
                    }

                    DB::table('jornadas_consolidadas')->where('id', $row->id)
                        ->update(['fracciones_evento' => json_encode($nuevo)]);
                }
            });
    }
};
