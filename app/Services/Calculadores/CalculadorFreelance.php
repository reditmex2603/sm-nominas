<?php

namespace App\Services\Calculadores;

use App\Enums\TipoColaborador;
use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Services\FuzzyMatcher;

/**
 * Nómina Freelance: pago por evento ponderado por etapas registradas en días validados,
 * más días adicionales pactados y menos anticipos ligados al evento por concepto.
 */
class CalculadorFreelance extends AbstractCalculadorNomina
{
    public function calcular(
        Colaborador $col,
        Evento $evento,
        int $diasAdicionales = 0,
        float $compensacion = 0,
    ): array {
        $registros = $this->registrosDeEvento($col->id, $evento);

        $etapas = $this->etapasUnicas(
            $registros->filter(fn ($r) => $r['contabiliza'])->pluck('etapa'),
        );
        $pct = $this->pctDeEtapas($etapas);

        $pagoBase = (float) $evento->pago_por_evento_completo * $pct;
        $pagoExtras = $diasAdicionales * (float) ($col->extra_dia_adicional ?? 0);

        // Anticipos vinculados a este evento (fuzzy match en concepto)
        $anticipos = Anticipo::where('colaborador_id', $col->id)->get()
            ->filter(fn ($a) => ! empty($a->concepto) && FuzzyMatcher::match($a->concepto, $evento->nombre))
            ->sum('monto');

        $totalFinal = $pagoBase + $pagoExtras + $compensacion - $anticipos;

        $existente = $this->nomina($col->id, $evento->id, null, null);

        return [
            'tipo' => TipoColaborador::Freelance->value,
            'tipo_colaborador' => TipoColaborador::Freelance->value,
            'colaborador_id' => $col->id,
            'periodo_inicio' => null,
            'periodo_fin' => null,
            'evento_id' => $evento->id,
            'dias' => $diasAdicionales,
            'sueldo_diario' => (float) $evento->pago_por_evento_completo,
            'total_base' => round($pagoBase, 2),
            'bonos_evento' => round($pagoExtras, 2),
            'compensaciones' => $compensacion,
            'anticipos' => round((float) $anticipos, 2),
            'total_final' => round($totalFinal, 2),
            '_etapas' => $etapas->all(),
            '_registros' => $registros->all(),
            '_porcentaje' => $pct * 100,
            '_pago_base' => round($pagoBase, 2),
            '_pago_extras' => round($pagoExtras, 2),
            'estado' => $existente?->estado,
            'nomina_id' => $existente?->id,
        ];
    }
}
