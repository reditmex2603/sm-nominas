<?php

namespace App\Services\Calculadores;

use App\Enums\TipoColaborador;
use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Services\FuzzyMatcher;
use App\Support\Money;

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

        $pagoBase = Money::from($evento->pago_por_evento_completo)->multiplicarPor($pct);
        $pagoExtras = Money::from($col->extra_dia_adicional ?? 0)->multiplicarPor($diasAdicionales);

        // Anticipos vinculados a este evento (fuzzy match en concepto)
        $anticipos = Money::from(Anticipo::where('colaborador_id', $col->id)->get()
            ->filter(fn ($a) => ! empty($a->concepto) && FuzzyMatcher::match($a->concepto, $evento->nombre))
            ->sum('monto'));

        $totalFinal = $pagoBase->sumar($pagoExtras)->sumar($compensacion)->restar($anticipos);

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
            'total_base' => $pagoBase->toFloat(),
            'bonos_evento' => $pagoExtras->toFloat(),
            'compensaciones' => $compensacion,
            'anticipos' => $anticipos->toFloat(),
            'total_final' => $totalFinal->toFloat(),
            '_etapas' => $etapas->all(),
            '_registros' => $registros->all(),
            '_porcentaje' => $pct * 100,
            '_pago_base' => $pagoBase->toFloat(),
            '_pago_extras' => $pagoExtras->toFloat(),
            'estado' => $existente?->estado,
            'nomina_id' => $existente?->id,
        ];
    }
}
