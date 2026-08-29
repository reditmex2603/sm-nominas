<?php

namespace App\Services\Calculadores;

use App\Enums\CategoriaColaborador;
use App\Enums\TipoColaborador;
use App\Enums\TipoPago;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\JornadaConsolidada;
use App\Models\RegistroNormalizado;
use App\Services\FuzzyMatcher;
use Illuminate\Support\Carbon;

/**
 * Nómina del personal Base: sueldo semanal condicionado a asistencia (L–S), bono de
 * séptimo día, extra por evento según categoría/nivel/tamaño ponderado por etapas.
 */
class CalculadorBase extends AbstractCalculadorNomina
{
    public function calcular(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        $jornadas = $this->jornadasValidadas($col->id, $inicio, $fin);

        // El extra de evento es un monto FIJO según la categoría del colaborador Y el tamaño del
        // evento (MEDIANO/GRANDE, ver Parámetros del Sistema) — CHICO nunca genera bono. Igual
        // que en Freelance, no se paga al 100% si el colaborador no participó en todas las
        // etapas (montaje/show/desmontaje) de ese día: se pondera por el % de etapas registradas
        // (etapasUnicas/pctDeEtapas).
        //
        // Si el colaborador solo asistió al evento ese día (sin registrar actividad de bodega),
        // igual recibe el día completo (sueldo_diario × fracción del tipo_pago) MÁS el extra de
        // categoría ponderado por su % de participación en las etapas — no se le quita el día.
        $registrosEventoPorFecha = RegistroNormalizado::where('colaborador_id', $col->id)
            ->where('tipo_actividad', 'Evento')
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->get()
            ->groupBy(fn ($r) => $r->fecha->format('Y-m-d'));

        $evaluaciones = $jornadas
            ->sortBy('fecha')
            ->map(fn ($j) => $this->evaluarDiaBase($j, $col->categoria, $col->nivel, $col->compensacion_pct, $registrosEventoPorFecha))
            ->values();

        $diasTotales = $evaluaciones->sum('dia_fraccion');
        $bonosEvento = $evaluaciones->sum('bono_evento');
        $totalBase = (float) $col->sueldo_diario * $diasTotales;
        $compensacionTotal = $evaluaciones->flatMap(fn ($d) => $d['eventos_dia'])->sum('compensacion');

        // Paso 4: bono séptimo día (agrupa por semana lun-sáb)
        $bonoSeptimoDia = $this->bonoSeptimoDia($col, $jornadas);

        $existente = $this->nomina($col->id, null, $inicio, $fin);

        // Paso 5: anticipos en el rango
        $anticipos = $this->anticiposEnRango($col->id, $inicio, $fin);

        // Préstamos: cuotas con fecha programada en el rango (ver prestamosEnRango).
        $prestamosInfo = $this->prestamosEnRango($col->id, $inicio, $fin, $existente?->id);
        $prestamos = $prestamosInfo['total'];

        // Paso 7
        $totalFinal = $totalBase + $bonosEvento + $bonoSeptimoDia + $compensacion - $anticipos - $prestamos;

        $jornadasResumen = $evaluaciones->all();

        return [
            'tipo' => TipoColaborador::Base->value,
            'tipo_colaborador' => TipoColaborador::Base->value,
            'colaborador_id' => $col->id,
            'periodo_inicio' => $inicio->format('Y-m-d'),
            'periodo_fin' => $fin->format('Y-m-d'),
            'evento_id' => null,
            'dias' => round($diasTotales, 2),
            'sueldo_diario' => (float) $col->sueldo_diario,
            'total_base' => round($totalBase, 2),
            'bonos_evento' => round($bonosEvento + $bonoSeptimoDia, 2),
            'compensaciones' => $compensacion,
            'anticipos' => round($anticipos, 2),
            'prestamos' => round($prestamos, 2),
            'total_final' => round($totalFinal, 2),
            // desglose interno para la UI
            '_bonos_evento_puro' => round($bonosEvento, 2),
            '_bono_septimo' => round($bonoSeptimoDia, 2),
            '_sueldo_diario' => (float) $col->sueldo_diario,
            '_compensacion_total' => round((float) $compensacionTotal, 2),
            '_jornadas' => $jornadasResumen,
            '_categoria' => $col->categoria,
            '_nivel' => $col->nivel,
            '_compensacion_pct' => $col->compensacion_pct,
            '_prestamo_detalle' => $prestamosInfo['detalle'],
            'estado' => $existente?->estado,
            'nomina_id' => $existente?->id,
        ];
    }

    /** Bono del séptimo día: heredado de AbstractCalculadorNomina. */

    /**
     * Evalúa un día de Base: cuánto cuenta para días trabajados y cuánto de extra de evento
     * genera, ponderando por participación en etapas. Un mismo día puede tener registros de
     * MÁS DE UN evento (ej. el colaborador participó parcialmente en dos eventos distintos);
     * cada evento se pondera y paga por separado según su propio % de etapas y su propio
     * tamaño (CHICO no genera bono), y los extras se suman.
     */
    private function evaluarDiaBase(JornadaConsolidada $j, CategoriaColaborador|string|null $categoria, ?int $nivel, ?int $compensacionPct, $registrosEventoPorFecha): array
    {
        $fecha = Carbon::parse($j->fecha)->format('Y-m-d');
        $eventosDelDia = Evento::extraerDeDetalle($j->detalle ?? '');
        $multiEvento = $eventosDelDia->count() >= 2;

        // El sueldo base (Bodega) siempre se paga completo por jornada trabajada — el
        // traslape (uno o más eventos) nunca reduce el sueldo del día, solo pondera el bono
        // de cada evento (ver fracciones_evento más abajo).
        $fraccionDia = 1.0;
        $fraccionEvento = $this->fraccionEventoDia($j);

        $base = [
            'fecha' => $fecha,
            'tipo_pago' => $j->tipo_pago->value,
            'traslape_pct' => $j->traslape_pct,
            'compensacion_activa' => (bool) $j->compensacion_activa,
            'detalle' => $j->detalle,
            'extras' => $j->extras,
        ];

        if ($fraccionEvento === 0) {
            return array_merge($base, [
                'dia_fraccion' => $fraccionDia,
                'bono_evento' => 0.0,
                'eventos_dia' => [],
            ]);
        }

        // El día se paga completo (según su tipo_pago) haya o no bodega registrada ese día;
        // solo el extra de categoría se pondera por la participación en etapas, evento por evento.
        $registrosDelDia = $registrosEventoPorFecha->get($fecha) ?? collect();
        $fraccionesEvento = $j->fracciones_evento ?? [];

        $bonoTotal = 0.0;
        $eventosDia = [];

        foreach ($eventosDelDia as $evento) {
            $etapasDelEvento = $registrosDelDia
                ->filter(fn ($r) => ! empty($r->evento_raw) && FuzzyMatcher::match($r->evento_raw, $evento->nombre))
                ->pluck('etapa');

            $pctEtapas = $this->pctDeEtapas($this->etapasUnicas($etapasDelEvento));
            $extraPorCategoria = $this->extraCategoriaDelEvento($categoria, $nivel, $evento);

            // Con un solo evento el día se usa el % del día (tipo_pago, como antes); con 2+,
            // cada evento tiene su propio porcentaje (1-100, definido libremente por el admin)
            // elegido aparte, porque el desempeño del colaborador puede variar por evento.
            $porcentajeEvento = $fraccionesEvento[$evento->id] ?? 100;
            $fraccionDelEvento = $multiEvento
                ? ((int) $porcentajeEvento) / 100
                : $fraccionEvento;

            $bono = round($extraPorCategoria * $fraccionDelEvento * $pctEtapas, 2);

            // Compensación: bono extra opcional, activado por jornada en el Panel de Validación.
            // Se calcula sobre el bono YA ponderado (mismo % de etapas y misma fracción de
            // traslape que el bono normal) — es un "bono sobre el bono", no un monto aparte.
            $compensacion = $j->compensacion_activa
                ? round($bono * (($compensacionPct ?? 0) / 100), 2)
                : 0.0;

            $bonoTotal += $bono + $compensacion;
            $eventosDia[] = [
                'nombre' => $evento->nombre,
                'tamano' => $evento->tamano,
                'pct_etapas' => round($pctEtapas * 100, 2),
                'bono' => round($bono + $compensacion, 2),
                'compensacion' => $compensacion,
                'fraccion' => $multiEvento ? (int) $porcentajeEvento : null,
            ];
        }

        return array_merge($base, [
            'dia_fraccion' => $fraccionDia,
            'bono_evento' => round($bonoTotal, 2),
            'eventos_dia' => $eventosDia,
        ]);
    }

    /** Fracción del extra de evento (Base) según tipo_pago del día — 0 si el día no califica. */
    private function fraccionEventoDia(JornadaConsolidada $j): float
    {
        return match ($j->tipo_pago) {
            TipoPago::JornadaCompletaEvento => 1.0,
            TipoPago::Traslape => ((int) ($j->traslape_pct ?? 0)) / 100,
            default => 0.0,
        };
    }
}
