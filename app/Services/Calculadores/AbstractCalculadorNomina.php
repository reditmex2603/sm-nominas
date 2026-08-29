<?php

namespace App\Services\Calculadores;

use App\Enums\CategoriaColaborador;
use App\Enums\EstadoCuota;
use App\Enums\EtapaEvento;
use App\Enums\TamanoEvento;
use App\Enums\TipoActividad;
use App\Enums\TipoPago;
use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\ParametroSistema;
use App\Models\PrestamoCuota;
use App\Models\RegistroNormalizado;
use App\Services\FuzzyMatcher;
use Illuminate\Support\Carbon;

/**
 * Base común de los calculadores de nómina: comparte los helpers de consulta a BD
 * y la lógica de etapas/extras que usan varias estrategias. Cada tipo de colaborador
 * implementa su propio `calcular()`.
 */
abstract class AbstractCalculadorNomina
{
    /**
     * Parámetro de Parámetros del Sistema que define el extra de evento (Base) según la
     * categoría del colaborador, su NIVEL (1 o 2 dentro de esa categoría) Y el tamaño del
     * evento. CHICO no tiene entrada: nunca genera bono, aunque el admin fuerce manualmente
     * "Jornada + Evento" ese día.
     */
    protected const PARAMETRO_BONO_POR_CATEGORIA_NIVEL_TAMANO = [
        'Encargado de área' => [
            1 => ['MEDIANO' => 'bono_evento_encargado_nivel1_mediano', 'GRANDE' => 'bono_evento_encargado_nivel1_grande'],
            2 => ['MEDIANO' => 'bono_evento_encargado_nivel2_mediano', 'GRANDE' => 'bono_evento_encargado_nivel2_grande'],
        ],
        'Técnico' => [
            1 => ['MEDIANO' => 'bono_evento_tecnico_nivel1_mediano', 'GRANDE' => 'bono_evento_tecnico_nivel1_grande'],
            2 => ['MEDIANO' => 'bono_evento_tecnico_nivel2_mediano', 'GRANDE' => 'bono_evento_tecnico_nivel2_grande'],
        ],
        'Stagehand SM' => [
            1 => ['MEDIANO' => 'bono_evento_stagehand_nivel1_mediano', 'GRANDE' => 'bono_evento_stagehand_nivel1_grande'],
            2 => ['MEDIANO' => 'bono_evento_stagehand_nivel2_mediano', 'GRANDE' => 'bono_evento_stagehand_nivel2_grande'],
        ],
    ];

    protected function jornadasValidadas(int $colaboradorId, Carbon $inicio, Carbon $fin)
    {
        return JornadaConsolidada::where('colaborador_id', $colaboradorId)
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('validado', true)
            ->whereNotIn('tipo_pago', [TipoPago::SinPago, TipoPago::ErrorEvento])
            ->orderBy('fecha')
            ->get();
    }

    /**
     * Monto del extra de evento (Base) según categoría, nivel y tamaño de evento. CHICO = $0
     * siempre. Público porque también lo usa `EventoController` para la cotización de nómina
     * (mismos parámetros, una sola fuente de verdad).
     */
    public function extraCategoriaDelEvento(CategoriaColaborador|string|null $categoria, ?int $nivel, ?Evento $evento): float
    {
        if (! $evento || ! $nivel || $evento->tamano === TamanoEvento::Chico) {
            return 0.0;
        }

        $categoriaValor = $categoria instanceof CategoriaColaborador ? $categoria->value : $categoria;
        $clave = self::PARAMETRO_BONO_POR_CATEGORIA_NIVEL_TAMANO[$categoriaValor][$nivel][$evento->tamano->value] ?? null;

        return $clave ? (float) ParametroSistema::get($clave, 0) : 0.0;
    }

    protected function anticiposEnRango(int $colaboradorId, Carbon $inicio, Carbon $fin): float
    {
        return (float) Anticipo::where('colaborador_id', $colaboradorId)
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->sum('monto');
    }

    /**
     * Cuotas de préstamo con fecha programada en el rango, disponibles para deducir: PENDIENTE
     * y (sin ligar a ninguna nómina O ya ligadas a $nominaActualId). El segundo caso permite
     * recalcular/regardar la MISMA nómina sin perder la deducción (si solo buscáramos
     * `historico_nomina_id IS NULL`, la segunda vez que se guarda la misma nómina no encontraría
     * nada porque la cuota ya quedó ligada a esa nómina desde el primer guardado).
     */
    protected function prestamosEnRango(int $colaboradorId, Carbon $inicio, Carbon $fin, ?int $nominaActualId): array
    {
        $cuotas = PrestamoCuota::whereHas('prestamo', fn ($q) => $q->where('colaborador_id', $colaboradorId))
            ->where('estado', EstadoCuota::Pendiente)
            ->whereBetween('fecha_programada', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where(function ($q) use ($nominaActualId) {
                $q->whereNull('historico_nomina_id');
                if ($nominaActualId) {
                    $q->orWhere('historico_nomina_id', $nominaActualId);
                }
            })
            ->with('prestamo:id,concepto')
            ->orderBy('fecha_programada')
            ->get();

        return [
            'total' => (float) $cuotas->sum('monto'),
            'detalle' => $cuotas->map(fn ($c) => [
                'id' => $c->id,
                'prestamo_id' => $c->prestamo_id,
                'concepto' => $c->prestamo?->concepto,
                'numero_plazo' => $c->numero_plazo,
                'monto' => (float) $c->monto,
                'fecha_programada' => $c->fecha_programada->format('Y-m-d'),
            ])->values()->all(),
        ];
    }

    protected function nomina(int $colaboradorId, ?int $eventoId, ?Carbon $inicio, ?Carbon $fin): ?HistoricoNomina
    {
        $q = HistoricoNomina::where('colaborador_id', $colaboradorId);

        if ($eventoId !== null) {
            $q->where('evento_id', $eventoId);
        } else {
            $q->where('periodo_inicio', $inicio?->format('Y-m-d'))
                ->where('periodo_fin', $fin?->format('Y-m-d'));
        }

        return $q->latest('id')->first();
    }

    /** Bono del séptimo día: suma un sueldo diario por cada semana (lun-sáb) con los días requeridos. */
    protected function bonoSeptimoDia(Colaborador $col, $jornadas): float
    {
        $diasBonoSeptimo = (int) ParametroSistema::get('dias_bono_septimo', 6);
        $bonoSeptimoDia = 0.0;

        $porSemana = $jornadas->groupBy(
            fn ($j) => Carbon::parse($j->fecha)->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
        );

        foreach ($porSemana as $jornadasSemana) {
            // dayOfWeek: 0=Dom,1=Lun,...,6=Sáb  →  requeridos 1-6
            $diasPresentes = $jornadasSemana
                ->map(fn ($j) => Carbon::parse($j->fecha)->dayOfWeek)
                ->filter(fn ($d) => $d >= 1 && $d <= 6)
                ->unique()
                ->count();

            if ($diasPresentes >= $diasBonoSeptimo) {
                $bonoSeptimoDia += (float) $col->sueldo_diario;
            }
        }

        return $bonoSeptimoDia;
    }

    /** Aplana etapas combinadas en un solo registro (ej. "Montaje, Show") y quita duplicados. */
    protected function etapasUnicas($etapasCrudas)
    {
        return collect($etapasCrudas)
            ->filter()
            ->flatMap(fn ($e) => array_map('trim', explode(',', is_string($e) ? $e : $e->value)))
            ->filter()
            ->unique()
            ->values();
    }

    /** Suma el % de cada etapa (Montaje 25%, Show 50%, Desmontaje 25%), topado en 100%. */
    protected function pctDeEtapas($etapasUnicas): float
    {
        return min(1.0, collect($etapasUnicas)->sum(fn ($e) => EtapaEvento::tryFrom($e)?->porcentaje() ?? 0));
    }

    /** Registros de asistencia del colaborador para ese evento, con si su jornada ya está validada. */
    protected function registrosDeEvento(int $colaboradorId, Evento $evento)
    {
        $fechasValidas = JornadaConsolidada::where('colaborador_id', $colaboradorId)
            ->where('validado', true)
            ->whereNotIn('tipo_pago', [TipoPago::SinPago, TipoPago::ErrorEvento])
            ->pluck('fecha')
            ->map(fn ($f) => $f->format('Y-m-d'))
            ->flip();

        return RegistroNormalizado::where('colaborador_id', $colaboradorId)
            ->where('tipo_actividad', TipoActividad::Evento)
            ->orderBy('fecha')
            ->get()
            ->filter(fn ($r) => ! empty($r->evento_raw) && FuzzyMatcher::match($r->evento_raw, $evento->nombre))
            ->map(fn ($r) => [
                'fecha' => $r->fecha->format('Y-m-d'),
                'etapa' => $r->etapa,
                'extras' => $r->extras,
                'contabiliza' => $fechasValidas->has($r->fecha->format('Y-m-d')),
            ])
            ->values();
    }
}
