<?php

namespace App\Http\Controllers;

use App\Enums\EstadoNomina;
use App\Http\Requests\CalcularNominaRequest;
use App\Http\Requests\FreelanceDatosRequest;
use App\Http\Requests\GuardarNominaRequest;
use App\Models\Auditoria;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\PrestamoCuota;
use App\Models\RegistroNormalizado;
use App\Services\FuzzyMatcher;
use App\Services\NominaCalculator;
use App\Support\Documentos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class NominaController extends Controller
{
    public function calcular(CalcularNominaRequest $request, NominaCalculator $calc): JsonResponse
    {
        $tipo = $request->input('tipo');
        $ini = null;
        $fin = null;
        $evento = null;

        if (in_array($tipo, ['COLABORADOR BASE', 'CONDUCTOR', 'CONDUCTOR BASE'], true)) {
            /** @var array{tipo: string, colaborador_id: int, inicio: string, fin: string, compensacion?: mixed} $validated */
            $validated = $request->validated();

            /** @var Colaborador $col */
            $col = Colaborador::findOrFail($validated['colaborador_id']);
            $ini = Carbon::parse($validated['inicio']);
            $fin = Carbon::parse($validated['fin']);
            $comp = (float) ($validated['compensacion'] ?? 0);

            $desglose = match ($tipo) {
                'COLABORADOR BASE' => $calc->calcularBase($col, $ini, $fin, $comp),
                'CONDUCTOR' => $calc->calcularConductor($col, $ini, $fin, $comp),
                default => $calc->calcularConductorBase($col, $ini, $fin, $comp),
            };

        } elseif ($tipo === 'FREELANCE') {
            /** @var array{tipo: string, colaborador_id: int, evento_id: int, dias_adicionales?: int, compensacion?: mixed} $validated */
            $validated = $request->validated();

            /** @var Colaborador $col */
            $col = Colaborador::findOrFail($validated['colaborador_id']);
            /** @var Evento $evento */
            $evento = Evento::findOrFail($validated['evento_id']);
            $comp = (float) ($validated['compensacion'] ?? 0);

            $desglose = $calc->calcularFreelance(
                $col,
                $evento,
                (int) ($validated['dias_adicionales'] ?? 0),
                $comp,
            );

        } else {
            return response()->json(['error' => 'Tipo de colaborador no válido'], 422);
        }

        $sinValidar = $this->contarSinValidar($tipo, $col->id, $ini, $fin, $evento);

        return response()->json(array_merge($desglose, ['jornadas_sin_validar' => $sinValidar]));
    }

    public function guardar(GuardarNominaRequest $request, NominaCalculator $calc): RedirectResponse
    {
        $tipo = $request->input('tipo');
        $ini = null;
        $fin = null;
        $evento = null;

        if (in_array($tipo, ['COLABORADOR BASE', 'CONDUCTOR', 'CONDUCTOR BASE'], true)) {
            /** @var array{tipo: string, colaborador_id: int, inicio: string, fin: string, compensacion?: mixed, comentario?: string|null} $validated */
            $validated = $request->validated();

            /** @var Colaborador $col */
            $col = Colaborador::findOrFail($validated['colaborador_id']);
            $ini = Carbon::parse($validated['inicio']);
            $fin = Carbon::parse($validated['fin']);
            $comp = (float) ($validated['compensacion'] ?? 0);

            $desglose = match ($tipo) {
                'COLABORADOR BASE' => $calc->calcularBase($col, $ini, $fin, $comp),
                'CONDUCTOR' => $calc->calcularConductor($col, $ini, $fin, $comp),
                default => $calc->calcularConductorBase($col, $ini, $fin, $comp),
            };

        } else {
            /** @var array{tipo: string, colaborador_id: int, evento_id: int, dias_adicionales?: int, compensacion?: mixed, comentario?: string|null} $validated */
            $validated = $request->validated();

            /** @var Colaborador $col */
            $col = Colaborador::findOrFail($validated['colaborador_id']);
            /** @var Evento $evento */
            $evento = Evento::findOrFail($validated['evento_id']);
            $comp = (float) ($validated['compensacion'] ?? 0);

            $desglose = $calc->calcularFreelance(
                $col,
                $evento,
                (int) ($validated['dias_adicionales'] ?? 0),
                $comp,
            );
        }

        // Bloquear si ya está PAGADO
        if ($desglose['estado'] === EstadoNomina::Pagado) {
            return back()->with('error', 'Esta nómina ya fue pagada y no puede modificarse.');
        }

        // Tope anti-negativo: la compensación/descuento (y deductivas) no pueden dejar en rojo.
        if ((float) $desglose['total_final'] < 0) {
            return back()->withErrors([
                'compensacion' => 'El total a pagar no puede ser negativo. Revisa la compensación, anticipos o préstamos.',
            ]);
        }

        // Bloquear si hay jornadas sin validar en el período
        $sinValidar = $this->contarSinValidar($tipo, $col->id, $ini, $fin, $evento);
        if ($sinValidar > 0) {
            $plural = $sinValidar === 1 ? 'jornada sin validar' : 'jornadas sin validar';

            return back()->withErrors([
                'jornadas_sin_validar' => "Hay {$sinValidar} {$plural} en este período. Valídalas en el Panel de Validación antes de guardar la nómina.",
            ]);
        }

        // Solo las claves "_..." son el desglose interno de la UI (jornadas/registros/rutas
        // evaluados); se congelan tal como quedaron al guardar, para que el Historial pueda
        // mostrar exactamente lo que se pagó aunque las reglas de negocio cambien después.
        $desgloseInterno = array_filter(
            $desglose,
            fn ($valor, $clave) => str_starts_with($clave, '_'),
            ARRAY_FILTER_USE_BOTH,
        );

        $nomina = HistoricoNomina::updateOrCreate(
            $this->claveUnica($desglose),
            [
                'tipo_colaborador' => $desglose['tipo_colaborador'],
                'dias' => $desglose['dias'],
                'sueldo_diario' => $desglose['sueldo_diario'],
                'total_base' => $desglose['total_base'],
                'bonos_evento' => $desglose['bonos_evento'],
                'compensaciones' => $desglose['compensaciones'],
                'comentario' => $validated['comentario'] ?? null,
                'anticipos' => $desglose['anticipos'],
                'prestamos' => $desglose['prestamos'] ?? 0,
                'total_final' => $desglose['total_final'],
                'estado' => 'PENDIENTE',
                'fecha_calculo' => now(),
                'desglose' => $desgloseInterno,
            ],
        );

        // Liga las cuotas de préstamo incluidas en este cálculo a la nómina — quedan PENDIENTE
        // hasta que la nómina se marque PAGADO (ver pagar()). Solo aplica a Base/Conductor.
        /** @var array<int, array{id: int}> $prestamoDetalle */
        $prestamoDetalle = $desglose['_prestamo_detalle'] ?? [];
        $cuotaIds = collect($prestamoDetalle)->pluck('id');
        if ($cuotaIds->isNotEmpty()) {
            PrestamoCuota::whereIn('id', $cuotaIds)->update(['historico_nomina_id' => $nomina->id]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nómina guardada como PENDIENTE.']);

        return back();
    }

    /** Eventos con asistencia registrada por el colaborador y sus registros editables, agrupados por evento. */
    public function freelanceDatos(FreelanceDatosRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $registros = RegistroNormalizado::where('colaborador_id', $validated['colaborador_id'])
            ->where('tipo_actividad', 'Evento')
            ->orderBy('fecha')
            ->get();

        $jornadasPorFecha = JornadaConsolidada::where('colaborador_id', $validated['colaborador_id'])
            ->get(['fecha', 'validado'])
            ->keyBy(fn ($j) => $j->fecha->format('Y-m-d'));

        $eventos = Evento::orderBy('nombre')->get(['id', 'nombre', 'tamano', 'pago_por_evento_completo']);
        $registrosPorEvento = [];

        foreach ($eventos as $evento) {
            $delEvento = $registros->filter(
                fn ($r) => ! empty($r->evento_raw) && FuzzyMatcher::match($r->evento_raw, $evento->nombre),
            );

            if ($delEvento->isEmpty()) {
                continue;
            }

            $registrosPorEvento[$evento->id] = $delEvento->map(fn ($r) => [
                'id' => $r->id,
                'fecha' => $r->fecha->format('Y-m-d'),
                'etapa' => $r->etapa,
                'extras' => $r->extras,
                'comentarios' => $r->comentarios,
                'evidencia_url' => Documentos::url($r->evidencia_path),
                'jornada_validada' => (bool) optional($jornadasPorFecha->get($r->fecha->format('Y-m-d')))->validado,
            ])->values();
        }

        return response()->json([
            'eventos' => $eventos->whereIn('id', array_keys($registrosPorEvento))->values(),
            'registros' => $registrosPorEvento,
        ]);
    }

    public function pagar(Request $request, HistoricoNomina $nomina): RedirectResponse
    {
        if ($nomina->estado === EstadoNomina::Pagado) {
            return back()->with('error', 'Esta nómina ya fue marcada como pagada.');
        }

        $nomina->update(['estado' => 'PAGADO']);

        // Las cuotas de préstamo ligadas a esta nómina ya se cobraron — pasan de PENDIENTE a
        // PAGADA para no volver a descontarse en un cálculo futuro.
        PrestamoCuota::where('historico_nomina_id', $nomina->id)
            ->update(['estado' => 'PAGADA', 'fecha_pago' => now()->toDateString()]);

        Auditoria::registrar('nomina.pagada', HistoricoNomina::class, $nomina->id, [
            'colaborador_id' => $nomina->colaborador_id,
            'tipo' => $nomina->tipo_colaborador->value,
            'total_final' => (float) $nomina->total_final,
            'periodo_inicio' => $nomina->periodo_inicio?->format('Y-m-d'),
            'periodo_fin' => $nomina->periodo_fin?->format('Y-m-d'),
            'evento_id' => $nomina->evento_id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nómina marcada como PAGADO.']);

        return back();
    }

    /** Elimina un cálculo PENDIENTE para poder recalcularlo desde cero (corrección de errores). */
    public function eliminar(HistoricoNomina $nomina): RedirectResponse
    {
        if ($nomina->estado === EstadoNomina::Pagado) {
            return back()->with('error', 'Esta nómina ya fue pagada y no puede eliminarse.');
        }

        Auditoria::registrar('nomina.eliminada', HistoricoNomina::class, $nomina->id, [
            'colaborador_id' => $nomina->colaborador_id,
            'tipo' => $nomina->tipo_colaborador->value,
            'total_final' => (float) $nomina->total_final,
        ]);

        // prestamo_cuotas.historico_nomina_id tiene ON DELETE SET NULL — al borrar la nómina,
        // cualquier cuota ligada queda automáticamente PENDIENTE y sin ligar, disponible para el
        // siguiente cálculo (nunca llegó a estado PAGADA porque eso solo ocurre en pagar()).
        $nomina->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nómina eliminada. Ya puedes volver a calcularla en el Panel de Validación.']);

        return back();
    }

    private function contarSinValidar(string $tipo, int $colaboradorId, ?Carbon $ini, ?Carbon $fin, ?Evento $evento): int
    {
        $q = JornadaConsolidada::where('colaborador_id', $colaboradorId)
            ->where('validado', false);

        if (in_array($tipo, ['COLABORADOR BASE', 'CONDUCTOR', 'CONDUCTOR BASE'], true) && $ini && $fin) {
            $q->whereBetween('fecha', [$ini->format('Y-m-d'), $fin->format('Y-m-d')]);
        } elseif ($tipo === 'FREELANCE' && $evento) {
            $q->where('detalle', 'like', '%Evento: '.$evento->nombre.'%');
        }

        return $q->count();
    }

    /** Clave para updateOrCreate: evita duplicar nóminas del mismo periodo. */
    /**
     * @param  array<string, mixed>  $d
     * @return array<string, int|string>
     */
    private function claveUnica(array $d): array
    {
        $base = ['colaborador_id' => $d['colaborador_id']];

        if ($d['evento_id'] !== null) {
            return array_merge($base, ['evento_id' => $d['evento_id']]);
        }

        return array_merge($base, [
            'periodo_inicio' => $d['periodo_inicio'],
            'periodo_fin' => $d['periodo_fin'],
        ]);
    }
}
