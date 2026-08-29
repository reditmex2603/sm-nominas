<?php

namespace App\Services;

use App\Enums\CategoriaColaborador;
use App\Enums\EstadoCuota;
use App\Enums\EtapaEvento;
use App\Enums\TamanoEvento;
use App\Enums\TipoActividad;
use App\Enums\TipoColaborador;
use App\Enums\TipoPago;
use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\ParametroSistema;
use App\Models\PrestamoCuota;
use App\Models\RegistroNormalizado;
use App\Models\TransporteDistancia;
use App\Models\TransporteTarifa;
use App\Models\TransporteVehiculo;
use Illuminate\Support\Carbon;

class NominaCalculator
{
    /**
     * Parámetro de Parámetros del Sistema que define el extra de evento (Base) según la
     * categoría del colaborador, su NIVEL (1 o 2 dentro de esa categoría) Y el tamaño del
     * evento. CHICO no tiene entrada: nunca genera bono, aunque el admin fuerce manualmente
     * "Jornada + Evento" ese día.
     */
    private const PARAMETRO_BONO_POR_CATEGORIA_NIVEL_TAMANO = [
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

    // ─────────────────────────────────────────────────────────────────
    // BASE (semanal)
    // ─────────────────────────────────────────────────────────────────

    public function calcularBase(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
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
            ->where('tipo_actividad', TipoActividad::Evento)
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

    // ─────────────────────────────────────────────────────────────────
    // FREELANCE (por evento)
    // ─────────────────────────────────────────────────────────────────

    public function calcularFreelance(
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

    // ─────────────────────────────────────────────────────────────────
    // CONDUCTOR (por periodo, por ruta)
    // ─────────────────────────────────────────────────────────────────

    public function calcularConductor(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        $jornadas = $this->jornadasValidadas($col->id, $inicio, $fin);
        $vehiculos = TransporteVehiculo::all();
        $distancias = TransporteDistancia::all();

        $rutas = [];
        $totalRutas = 0.0;

        foreach ($jornadas as $j) {
            $tarifa = $this->detectarTarifa($j->detalle ?? '', $vehiculos, $distancias);

            // Traslape (2 eventos/rutas el mismo día): se paga el % que definió el admin de la
            // tarifa, no la ruta completa.
            $fraccion = $this->fraccionPago($j);
            $tarifa['monto'] = round($tarifa['monto'] * $fraccion, 2);

            $totalRutas += $tarifa['monto'];
            $rutas[] = array_merge($tarifa, ['fecha' => $j->fecha->format('Y-m-d'), 'detalle' => $j->detalle, 'extras' => $j->extras]);
        }

        $existente = $this->nomina($col->id, null, $inicio, $fin);

        $anticipos = $this->anticiposEnRango($col->id, $inicio, $fin);

        $prestamosInfo = $this->prestamosEnRango($col->id, $inicio, $fin, $existente?->id);
        $prestamos = $prestamosInfo['total'];

        $totalFinal = $totalRutas + $compensacion - $anticipos - $prestamos;

        return [
            'tipo' => TipoColaborador::Conductor->value,
            'tipo_colaborador' => TipoColaborador::Conductor->value,
            'colaborador_id' => $col->id,
            'periodo_inicio' => $inicio->format('Y-m-d'),
            'periodo_fin' => $fin->format('Y-m-d'),
            'evento_id' => null,
            'dias' => count($jornadas),
            'sueldo_diario' => 0,
            'total_base' => round($totalRutas, 2),
            'bonos_evento' => 0,
            'compensaciones' => $compensacion,
            'anticipos' => round($anticipos, 2),
            'prestamos' => round($prestamos, 2),
            'total_final' => round($totalFinal, 2),
            '_rutas' => $rutas,
            '_total_rutas' => round($totalRutas, 2),
            '_prestamo_detalle' => $prestamosInfo['detalle'],
            'estado' => $existente?->estado,
            'nomina_id' => $existente?->id,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // CONDUCTOR BASE (por periodo): sueldo diario (Bodega/Ruta) + pago de rutas
    // ─────────────────────────────────────────────────────────────────

    public function calcularConductorBase(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        $jornadas = $this->jornadasValidadas($col->id, $inicio, $fin);
        $vehiculos = TransporteVehiculo::all();
        $distancias = TransporteDistancia::all();

        // Cada jornada válida (Bodega o Ruta) cuenta un día completo: recibe el sueldo diario
        // aunque ese día solo haya registrado rutas de transporte.
        $diasTotales = $jornadas->count();

        $rutas = [];
        $totalRutas = 0.0;

        foreach ($jornadas as $j) {
            $tarifa = $this->detectarTarifa($j->detalle ?? '', $vehiculos, $distancias);

            // Traslape (2 eventos/rutas el mismo día): se paga el % que definió el admin de la
            // tarifa, no la ruta completa (igual que Conductor puro).
            $fraccion = $this->fraccionPago($j);
            $tarifa['monto'] = round($tarifa['monto'] * $fraccion, 2);

            $totalRutas += $tarifa['monto'];
            $rutas[] = array_merge($tarifa, ['fecha' => $j->fecha->format('Y-m-d'), 'detalle' => $j->detalle, 'extras' => $j->extras]);
        }

        $totalBase = (float) $col->sueldo_diario * $diasTotales;

        // Bono séptimo día: igual que Base (agrupa por semana lun-sáb)
        $diasBonoSeptimo = (int) ParametroSistema::get('dias_bono_septimo', 6);
        $bonoSeptimoDia = 0.0;

        $porSemana = $jornadas->groupBy(
            fn ($j) => Carbon::parse($j->fecha)->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
        );

        foreach ($porSemana as $jornadasSemana) {
            $diasPresentes = $jornadasSemana
                ->map(fn ($j) => Carbon::parse($j->fecha)->dayOfWeek)
                ->filter(fn ($d) => $d >= 1 && $d <= 6)
                ->unique()
                ->count();

            if ($diasPresentes >= $diasBonoSeptimo) {
                $bonoSeptimoDia += (float) $col->sueldo_diario;
            }
        }

        $existente = $this->nomina($col->id, null, $inicio, $fin);

        $anticipos = $this->anticiposEnRango($col->id, $inicio, $fin);

        $prestamosInfo = $this->prestamosEnRango($col->id, $inicio, $fin, $existente?->id);
        $prestamos = $prestamosInfo['total'];

        $totalFinal = $totalBase + $bonoSeptimoDia + $totalRutas + $compensacion - $anticipos - $prestamos;

        return [
            'tipo' => TipoColaborador::ConductorBase->value,
            'tipo_colaborador' => TipoColaborador::ConductorBase->value,
            'colaborador_id' => $col->id,
            'periodo_inicio' => $inicio->format('Y-m-d'),
            'periodo_fin' => $fin->format('Y-m-d'),
            'evento_id' => null,
            'dias' => $diasTotales,
            'sueldo_diario' => (float) $col->sueldo_diario,
            'total_base' => round($totalBase, 2),
            'bonos_evento' => round($bonoSeptimoDia, 2),
            'compensaciones' => $compensacion,
            'anticipos' => round($anticipos, 2),
            'prestamos' => round($prestamos, 2),
            'total_final' => round($totalFinal, 2),
            '_bono_septimo' => round($bonoSeptimoDia, 2),
            '_sueldo_diario' => (float) $col->sueldo_diario,
            '_jornadas' => $jornadas->map(fn ($j) => [
                'fecha' => $j->fecha->format('Y-m-d'),
                'tipo_pago' => $j->tipo_pago->value,
                'traslape_pct' => $j->traslape_pct,
                'detalle' => $j->detalle,
                'extras' => $j->extras,
                'dia_fraccion' => 1.0,
                'bono_evento' => 0.0,
                'eventos_dia' => [],
            ])->values()->all(),
            '_rutas' => $rutas,
            '_total_rutas' => round($totalRutas, 2),
            '_prestamo_detalle' => $prestamosInfo['detalle'],
            'estado' => $existente?->estado,
            'nomina_id' => $existente?->id,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────

    private function jornadasValidadas(int $colaboradorId, Carbon $inicio, Carbon $fin)
    {
        return JornadaConsolidada::where('colaborador_id', $colaboradorId)
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('validado', true)
            ->whereNotIn('tipo_pago', [TipoPago::SinPago, TipoPago::ErrorEvento])
            ->orderBy('fecha')
            ->get();
    }

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

    /**
     * Fracción de pago (0.0–1.0) de la jornada según tipo_pago. TRASLAPE usa el porcentaje
     * (1-99) que el admin capturó en `traslape_pct`, ya no un 40%/50% fijo. Usado para la
     * tarifa de ruta (Conductor); Base no la usa para su sueldo (§ traslape nunca reduce el
     * sueldo del día, ver evaluarDiaBase).
     */
    private function fraccionPago(JornadaConsolidada $j): float
    {
        return match ($j->tipo_pago) {
            TipoPago::JornadaCompleta, TipoPago::JornadaCompletaEvento => 1.0,
            TipoPago::Traslape => ((int) ($j->traslape_pct ?? 0)) / 100,
            default => 0.0,
        };
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

    /** Registros de asistencia del colaborador para ese evento, con si su jornada ya está validada. */
    private function registrosDeEvento(int $colaboradorId, Evento $evento)
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

    /** Aplana etapas combinadas en un solo registro (ej. "Montaje, Show") y quita duplicados. */
    private function etapasUnicas($etapasCrudas)
    {
        return collect($etapasCrudas)
            ->filter()
            ->flatMap(fn ($e) => array_map('trim', explode(',', is_string($e) ? $e : $e->value)))
            ->filter()
            ->unique()
            ->values();
    }

    /** Suma el % de cada etapa (Montaje 25%, Show 50%, Desmontaje 25%), topado en 100%. */
    private function pctDeEtapas($etapasUnicas): float
    {
        return min(1.0, collect($etapasUnicas)->sum(fn ($e) => EtapaEvento::tryFrom($e)?->porcentaje() ?? 0));
    }

    private function anticiposEnRango(int $colaboradorId, Carbon $inicio, Carbon $fin): float
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
    private function prestamosEnRango(int $colaboradorId, Carbon $inicio, Carbon $fin, ?int $nominaActualId): array
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

    private function nomina(int $colaboradorId, ?int $eventoId, ?Carbon $inicio, ?Carbon $fin): ?HistoricoNomina
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

    private function detectarTarifa(string $detalle, $vehiculos, $distancias): array
    {
        $noDetectado = ['vehiculo' => 'No detectado', 'distancia' => 'No detectada', 'monto' => 0.0];

        if (! preg_match('/Manej[oó] un\(a\)\s+(.+?),\s*de\s+/iu', $detalle, $m)) {
            return $noDetectado;
        }

        $resto = $m[1]; // "{vehiculo} {distancia}"

        // Buscar distancia
        $distanciaEncontrada = null;
        $vehiculoTexto = $resto;

        foreach ($distancias as $dist) {
            $normDist = FuzzyMatcher::normDist($dist->nombre);
            $normResto = FuzzyMatcher::normDist($resto);

            if (str_contains($normResto, $normDist)) {
                $distanciaEncontrada = $dist;
                $pos = mb_stripos($normResto, $normDist);
                $vehiculoTexto = trim(mb_substr($resto, 0, $pos));
                break;
            }
        }

        // Sin distancia → intentar columna STANDBY
        if (! $distanciaEncontrada) {
            $distanciaEncontrada = $distancias->firstWhere('es_standby', true);
        }

        if (! $distanciaEncontrada) {
            return array_merge($noDetectado, ['vehiculo' => $vehiculoTexto ?: 'No detectado']);
        }

        // Buscar vehículo
        $vehiculoEncontrado = null;
        foreach ($vehiculos as $v) {
            if (FuzzyMatcher::match($vehiculoTexto, $v->nombre)) {
                $vehiculoEncontrado = $v;
                break;
            }
        }

        if (! $vehiculoEncontrado) {
            return [
                'vehiculo' => $vehiculoTexto ?: 'No detectado',
                'distancia' => $distanciaEncontrada->nombre,
                'monto' => 0.0,
            ];
        }

        $tarifa = TransporteTarifa::where('vehiculo_id', $vehiculoEncontrado->id)
            ->where('distancia_id', $distanciaEncontrada->id)
            ->value('tarifa');

        return [
            'vehiculo' => $vehiculoEncontrado->nombre,
            'distancia' => $distanciaEncontrada->nombre,
            'monto' => (float) ($tarifa ?? 0),
        ];
    }
}
