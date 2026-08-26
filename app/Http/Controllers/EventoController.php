<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\ParametroSistema;
use App\Models\ServicioProfesional;
use App\Models\TransporteUnidad;
use App\Models\Viatico;
use App\Rules\Telefono;
use App\Services\NominaCalculator;
use App\Support\Documentos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EventoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('eventos/Index', [
            'eventos' => Evento::orderBy('nombre')->get()->map(fn ($e) => $this->conFechasFormateadas($e)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:eventos',
            'lugar' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tamano' => 'required|in:CHICO,MEDIANO,GRANDE',
            'nombre_contratante' => 'nullable|string|max:255',
            'telefono_contratante' => ['nullable', new Telefono],
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => ['nullable', new Telefono],
            'descripcion' => 'nullable|string|max:5000',
            'observaciones_tecnicas' => 'nullable|string|max:5000',
            'enlace_ubicacion' => 'nullable|url|max:1000',
        ]);

        $claveDefault = 'pago_default_'.strtolower($validated['tamano']);
        $validated['pago_por_evento_completo'] = (float) ParametroSistema::get($claveDefault, 0);

        Evento::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Evento creado correctamente.']);

        return back();
    }

    public function update(Request $request, Evento $evento): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:eventos,nombre,'.$evento->id,
            'lugar' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tamano' => 'sometimes|in:CHICO,MEDIANO,GRANDE',
            'pago_por_evento_completo' => 'sometimes|numeric|min:0',
            'nombre_contratante' => 'nullable|string|max:255',
            'telefono_contratante' => ['sometimes', 'nullable', new Telefono],
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => ['sometimes', 'nullable', new Telefono],
            'descripcion' => 'nullable|string|max:5000',
            'observaciones_tecnicas' => 'nullable|string|max:5000',
            'enlace_ubicacion' => 'nullable|url|max:1000',
        ]);

        $evento->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Evento actualizado.']);

        return back();
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        if ($evento->nominas()->exists()) {
            return back()->withErrors(['delete' => 'El evento tiene nóminas registradas y no puede eliminarse.']);
        }

        if ($evento->viaticos()->exists()) {
            return back()->withErrors(['delete' => 'El evento tiene viáticos registrados y no puede eliminarse.']);
        }

        $evento->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Evento eliminado.']);

        return back();
    }

    public function asignacion(Evento $evento, NominaCalculator $calc): Response
    {
        $asignadosIds = $evento->colaboradores()->pluck('colaboradores.id');
        $asignados = $evento->colaboradores()
            ->get(['colaboradores.id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel', 'sueldo_diario']);

        $nomina = $this->nominaDelEvento($evento);
        $viaticos = $this->viaticosDelEvento($evento);
        $servicios = $this->serviciosDelEvento($evento);
        $cotizacion = $this->calcularCotizacion($evento, $asignados, $calc);

        $unidadesAsignadas = $evento->unidadesTransporte()
            ->with('vehiculo:id,nombre')
            ->orderBy('marca')
            ->get();
        $unidadesAsignadasIds = $unidadesAsignadas->pluck('id');
        $unidadesDisponibles = TransporteUnidad::with('vehiculo:id,nombre')
            ->whereNotIn('id', $unidadesAsignadasIds)
            ->orderBy('marca')
            ->get();

        return Inertia::render('eventos/Asignacion', [
            'evento' => $this->conFechasFormateadas($evento),
            'asignados' => $asignados,
            'disponibles' => $this->disponiblesFiltrados($evento, $asignadosIds, $asignados),
            'unidades_asignadas' => $unidadesAsignadas,
            'unidades_disponibles' => $unidadesDisponibles,
            'nomina' => $nomina,
            'viaticos' => $viaticos,
            'servicios' => $servicios,
            'requisitos' => $evento->requisitos_cotizacion ?? $this->requisitosVacios(),
            'cotizacion' => $cotizacion,
            'resumen' => $this->resumenDelEvento($evento, $asignados, $nomina, $viaticos, $servicios, $cotizacion),
        ]);
    }

    public function syncUnidades(Request $request, Evento $evento): RedirectResponse
    {
        $validated = $request->validate([
            'unidad_ids' => 'present|array',
            'unidad_ids.*' => 'integer|exists:transporte_unidades,id',
        ]);

        $evento->unidadesTransporte()->sync($validated['unidad_ids']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidades de transporte actualizadas.']);

        return back();
    }

    public function imprimirNomina(Evento $evento): Response
    {
        $resumen = $this->nominaDelEvento($evento);

        $allIds = $resumen['freelance']->pluck('nomina_id')
            ->merge($resumen['base']->pluck('nomina_id'))
            ->unique()
            ->values()
            ->all();

        $nominas = [];
        if (! empty($allIds)) {
            $nominas = HistoricoNomina::whereIn('id', $allIds)
                ->with('colaborador:id,nombre,apellidos')
                ->get()
                ->keyBy('id')
                ->toArray();
        }

        return Inertia::render('eventos/ImprimirNomina', [
            'evento' => $this->conFechasFormateadas($evento),
            'resumen' => $resumen,
            'nominas' => $nominas,
            'viaticos' => $this->viaticosDelEvento($evento),
        ]);
    }

    public function imprimirCotizacion(Evento $evento, NominaCalculator $calc): Response
    {
        $asignados = $evento->colaboradores()
            ->get(['colaboradores.id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel', 'sueldo_diario']);

        return Inertia::render('eventos/ImprimirCotizacion', [
            'evento' => $this->conFechasFormateadas($evento),
            'cotizacion' => $this->calcularCotizacion($evento, $asignados, $calc),
            'requisitos' => $evento->requisitos_cotizacion ?? $this->requisitosVacios(),
        ]);
    }

    public function imprimirResumen(Evento $evento, NominaCalculator $calc): Response
    {
        $asignados = $evento->colaboradores()
            ->get(['colaboradores.id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel', 'sueldo_diario']);

        $nomina = $this->nominaDelEvento($evento);
        $viaticos = $this->viaticosDelEvento($evento);
        $servicios = $this->serviciosDelEvento($evento);
        $cotizacion = $this->calcularCotizacion($evento, $asignados, $calc);

        return Inertia::render('eventos/ImprimirResumen', [
            'evento' => $this->conFechasFormateadas($evento),
            'resumen' => $this->resumenDelEvento($evento, $asignados, $nomina, $viaticos, $servicios, $cotizacion),
        ]);
    }

    /**
     * Ficha imprimible del evento: detalles generales (sin datos del contratante/contacto),
     * cotización, asignación y el perfil de cada colaborador asignado, en el formato del
     * perfil imprimible de un colaborador.
     */
    public function imprimirDetalles(Evento $evento, NominaCalculator $calc): Response
    {
        $asignados = $evento->colaboradores()
            ->with('perfil')
            ->get(['colaboradores.id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel', 'sueldo_diario']);

        $perfiles = $asignados->map(fn ($col) => [
            'colaborador' => [
                'id' => $col->id,
                'nombre' => $col->nombre,
                'apellidos' => $col->apellidos,
                'tipo' => $col->tipo,
                'categoria' => $col->categoria,
                'nivel' => $col->nivel,
                'sueldo_diario' => (float) $col->sueldo_diario,
            ],
            'perfil' => $col->perfil
                ? [
                    ...$col->perfil->toArray(),
                    'fotografia_url' => Documentos::url($col->perfil->fotografia_path),
                ]
                : null,
        ])->values()->all();

        $documentoCampos = ['placas', 'tarjeta_circulacion', 'poliza_seguro', 'verificacion', 'tenencia'];
        $unidades = $evento->unidadesTransporte()
            ->with('vehiculo:id,nombre')
            ->orderBy('marca')
            ->get()
            ->map(function ($u) use ($documentoCampos) {
                $extra = [];

                foreach ($documentoCampos as $campo) {
                    $path = $u->{"{$campo}_documento_path"};
                    $extra["{$campo}_documento_url"] = Documentos::url($path);
                }

                return [
                    ...$u->toArray(),
                    ...$extra,
                    'fotografia_url' => Documentos::url($u->fotografia_path),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('eventos/ImprimirDetalles', [
            'evento' => $this->conFechasFormateadas($evento),
            'cotizacion' => $this->calcularCotizacion($evento, $asignados, $calc),
            'requisitos' => $evento->requisitos_cotizacion ?? $this->requisitosVacios(),
            'perfiles' => $perfiles,
            'unidades' => $unidades,
        ]);
    }

    /** Guarda (o limpia, enviando todo en 0) los requisitos de personal para la cotización. */
    public function guardarRequisitos(Request $request, Evento $evento): RedirectResponse
    {
        $validated = $request->validate([
            'base' => 'required|array',
            'base.*' => 'required|array',
            'base.*.*' => 'required|integer|min:0|max:99',
            'freelance' => 'required|integer|min:0|max:99',
        ]);

        $evento->update(['requisitos_cotizacion' => $validated]);

        return back()->with('success', 'Requisitos de cotización guardados.');
    }

    private function requisitosVacios(): array
    {
        return [
            'base' => [
                'Encargado de área' => ['1' => 0, '2' => 0],
                'Técnico' => ['1' => 0, '2' => 0],
                'Stagehand SM' => ['1' => 0, '2' => 0],
            ],
            'freelance' => 0,
        ];
    }

    /**
     * Limita el Select de "disponibles" a colaboradores que cubren un requisito con cupo libre
     * (Base: su categoría+nivel exacto; Freelance: si aún faltan freelance) — Conductores no
     * forman parte de la cotización, se muestran sin restricción. Si no hay requisitos
     * capturados (todo en 0), Base/Freelance quedan bloqueados hasta que se definan en la
     * sección "Requisitos de personal" — solo se puede asignar Conductores.
     */
    private function disponiblesFiltrados(Evento $evento, $asignadosIds, $asignados): Collection
    {
        $todos = Colaborador::whereNotIn('id', $asignadosIds)
            ->orderBy('apellidos')
            ->get(['id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel']);

        $requisitos = $evento->requisitos_cotizacion ?? $this->requisitosVacios();

        $conteoBase = [];
        foreach ($asignados->where('tipo', 'COLABORADOR BASE') as $a) {
            $conteoBase[$a->categoria][$a->nivel] = ($conteoBase[$a->categoria][$a->nivel] ?? 0) + 1;
        }
        $conteoFreelance = $asignados->where('tipo', 'FREELANCE')->count();

        return $todos->filter(function ($c) use ($requisitos, $conteoBase, $conteoFreelance) {
            // Conductores base no participan en eventos (solo Bodega y Transporte).
            if ($c->tipo === 'CONDUCTOR') {
                return true;
            }

            if ($c->tipo === 'CONDUCTOR BASE') {
                return false;
            }

            if ($c->tipo === 'COLABORADOR BASE') {
                $requerido = $requisitos['base'][$c->categoria][(string) $c->nivel] ?? 0;
                $yaAsignados = $conteoBase[$c->categoria][$c->nivel] ?? 0;

                return $requerido > $yaAsignados;
            }

            // FREELANCE
            return ($requisitos['freelance'] ?? 0) > $conteoFreelance;
        })->values();
    }

    /**
     * Cotización de nómina: cuánto se pagaría si TODOS los colaboradores ya asignados al evento
     * asistieran el 100% de los días del evento y participaran en el 100% de las etapas — sin
     * compensación ni días adicionales. Usa el sueldo diario REAL de cada colaborador asignado
     * (no un promedio ni un parámetro aparte) y el mismo extra por categoría/nivel/tamaño que ya
     * usa el motor de nómina real.
     */
    private function calcularCotizacion(Evento $evento, $asignados, NominaCalculator $calc): array
    {
        $dias = ($evento->fecha_inicio && $evento->fecha_fin)
            ? $evento->fecha_inicio->diffInDays($evento->fecha_fin) + 1
            : null;

        $detalleBase = $asignados->where('tipo', 'COLABORADOR BASE')->map(function ($col) use ($evento, $calc, $dias) {
            $sueldo = $dias ? (float) $col->sueldo_diario * $dias : 0.0;
            $bono = $dias ? $calc->extraCategoriaDelEvento($col->categoria, $col->nivel, $evento) * $dias : 0.0;

            return [
                'colaborador_id' => $col->id,
                'nombre' => $col->nombre,
                'apellidos' => $col->apellidos,
                'categoria' => $col->categoria,
                'nivel' => $col->nivel,
                'sueldo' => round($sueldo, 2),
                'bono' => round($bono, 2),
                'total' => round($sueldo + $bono, 2),
            ];
        })->values();

        $freelanceCount = $asignados->where('tipo', 'FREELANCE')->count();
        $pagoFreelance = (float) $evento->pago_por_evento_completo;
        $totalFreelance = $freelanceCount * $pagoFreelance;
        $totalBase = (float) $detalleBase->sum('total');

        return [
            'dias' => $dias,
            'base' => $detalleBase,
            'total_base' => round($totalBase, 2),
            'freelance_count' => $freelanceCount,
            'pago_por_freelance' => round($pagoFreelance, 2),
            'total_freelance' => round($totalFreelance, 2),
            'total' => round($totalBase + $totalFreelance, 2),
        ];
    }

    /**
     * El cast 'date' serializa a ISO completo (ej. "2026-08-01T00:00:00.000000Z"), que
     * `<input type="date">` no acepta como value — lo reformatea a "Y-m-d" antes de enviarlo.
     */
    private function conFechasFormateadas(Evento $evento): array
    {
        return [
            ...$evento->toArray(),
            'fecha_inicio' => $evento->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $evento->fecha_fin?->format('Y-m-d'),
        ];
    }

    /**
     * Nóminas GUARDADAS (no cálculos en curso) relacionadas con este evento, para la pestaña
     * "Nómina" del detalle del evento.
     *
     * FREELANCE se paga por evento (evento_id ya es la relación directa). BASE se paga por
     * período semanal, que puede cubrir varios eventos — no hay relación directa, así que se
     * detecta buscando en el desglose YA CONGELADO (`_jornadas[].eventos_dia`) qué nóminas Base
     * efectivamente pagaron algún día de este evento; el monto atribuible es solo el bono de
     * evento de esos días (el sueldo diario no es específico de ningún evento).
     */
    private function nominaDelEvento(Evento $evento): array
    {
        $freelance = HistoricoNomina::where('evento_id', $evento->id)
            ->where('tipo_colaborador', 'FREELANCE')
            ->with('colaborador:id,nombre,apellidos')
            ->get()
            ->map(fn ($n) => [
                'nomina_id' => $n->id,
                'colaborador' => $n->colaborador,
                'estado' => $n->estado,
                'total_final' => (float) $n->total_final,
                'registros' => $n->desglose['_registros'] ?? [],
            ])
            ->values();

        $base = HistoricoNomina::where('tipo_colaborador', 'COLABORADOR BASE')
            ->whereNotNull('desglose')
            ->with('colaborador:id,nombre,apellidos')
            ->get()
            ->map(function ($n) use ($evento) {
                $jornadas = collect($n->desglose['_jornadas'] ?? [])
                    ->map(function ($j) use ($evento) {
                        $match = collect($j['eventos_dia'] ?? [])->firstWhere('nombre', $evento->nombre);

                        return $match ? [
                            'fecha' => $j['fecha'],
                            'tipo_pago' => $j['tipo_pago'],
                            'traslape_pct' => $j['traslape_pct'] ?? null,
                            'detalle' => $j['detalle'],
                            'bono' => (float) $match['bono'],
                            'pct_etapas' => $match['pct_etapas'],
                        ] : null;
                    })
                    ->filter()
                    ->values();

                if ($jornadas->isEmpty()) {
                    return null;
                }

                return [
                    'nomina_id' => $n->id,
                    'colaborador' => $n->colaborador,
                    'estado' => $n->estado,
                    'periodo_inicio' => $n->periodo_inicio?->format('Y-m-d'),
                    'periodo_fin' => $n->periodo_fin?->format('Y-m-d'),
                    'jornadas' => $jornadas,
                    'subtotal' => round((float) $jornadas->sum('bono'), 2),
                ];
            })
            ->filter()
            ->values();

        $totalPagado = (float) $freelance->where('estado', 'PAGADO')->sum('total_final')
            + (float) $base->where('estado', 'PAGADO')->sum('subtotal');

        $totalPorPagar = (float) $freelance->where('estado', 'PENDIENTE')->sum('total_final')
            + (float) $base->where('estado', 'PENDIENTE')->sum('subtotal');

        return [
            'freelance' => $freelance,
            'base' => $base,
            'subtotal_freelance' => round((float) $freelance->sum('total_final'), 2),
            'subtotal_base' => round((float) $base->sum('subtotal'), 2),
            'total_pagado' => round($totalPagado, 2),
            'total_por_pagar' => round($totalPorPagar, 2),
        ];
    }

    /** Viáticos registrados para este evento, para la pestaña "Viáticos" del detalle del evento. */
    private function viaticosDelEvento(Evento $evento): array
    {
        $items = Viatico::where('evento_id', $evento->id)
            ->with('colaborador:id,nombre,apellidos')
            ->orderBy('fecha', 'desc')
            ->get();

        return [
            'items' => $items,
            'subtotal' => round((float) $items->sum('monto'), 2),
        ];
    }

    /** Servicios profesionales registrados para este evento. */
    private function serviciosDelEvento(Evento $evento): array
    {
        $items = ServicioProfesional::where('evento_id', $evento->id)
            ->orderBy('fecha', 'desc')
            ->get();

        return [
            'items' => $items,
            'subtotal' => round((float) $items->sum('monto'), 2),
        ];
    }

    /**
     * Resumen de gastos e indicadores del evento, para la pestaña "Resumen". Consolida los
     * subtotales ya calculados (nóminas frecuentadas, viáticos y servicios profesionales), la
     * cotización proyectada y algunos promedios útiles. El "margen" es meramente proyectado:
     * compara lo cotizado (100% de participación) contra lo gastado hasta ahora.
     */
    private function resumenDelEvento(
        Evento $evento,
        Collection $asignados,
        array $nomina,
        array $viaticos,
        array $servicios,
        array $cotizacion
    ): array {
        $dias = $cotizacion['dias'];

        $baseCount = $asignados->where('tipo', 'COLABORADOR BASE')->count();
        $freelanceCount = $asignados->where('tipo', 'FREELANCE')->count();

        $subtotalNomina = round($nomina['subtotal_freelance'] + $nomina['subtotal_base'], 2);
        $totalGastos = round($subtotalNomina + $viaticos['subtotal'] + $servicios['subtotal'], 2);

        $cotizado = (float) $cotizacion['total'];
        $margen = round($cotizado - $totalGastos, 2);
        $rentabilidadPct = $cotizado > 0 ? round(($margen / $cotizado) * 100, 1) : null;

        $totalColaboradores = $asignados->count();

        return [
            'dias' => $dias,
            'total_colaboradores' => $totalColaboradores,
            'base_count' => $baseCount,
            'freelance_count' => $freelanceCount,
            'conductor_count' => $totalColaboradores - $baseCount - $freelanceCount,
            'pago_por_freelance' => $cotizacion['pago_por_freelance'],
            'subtotal_nomina_freelance' => $nomina['subtotal_freelance'],
            'subtotal_nomina_base' => $nomina['subtotal_base'],
            'subtotal_nomina' => $subtotalNomina,
            'subtotal_viaticos' => $viaticos['subtotal'],
            'subtotal_servicios' => $servicios['subtotal'],
            'total_gastos' => $totalGastos,
            'total_pagado' => $nomina['total_pagado'],
            'total_por_pagar' => $nomina['total_por_pagar'],
            'cotizacion_total' => $cotizado,
            'margen_proyectado' => $margen,
            'rentabilidad_pct' => $rentabilidadPct,
            'gasto_promedio_dia' => $dias ? round($totalGastos / $dias, 2) : null,
            'gasto_promedio_colaborador' => $totalColaboradores ? round($totalGastos / $totalColaboradores, 2) : null,
        ];
    }
}
