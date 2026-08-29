<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCuota;
use App\Enums\PeriodicidadPrestamo;
use App\Enums\TipoColaborador;
use App\Http\Requests\AplazarCuotasRequest;
use App\Http\Requests\DistribuirCuotasRequest;
use App\Http\Requests\StorePrestamoRequest;
use App\Models\Colaborador;
use App\Models\Prestamo;
use App\Models\PrestamoCuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PrestamoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('prestamos/Index', [
            'prestamos' => Prestamo::with(['colaborador:id,nombre,apellidos,tipo', 'cuotas'])
                ->orderBy('fecha_inicio', 'desc')
                ->get(),
            // Solo Base/Conductor/Conductor base: tienen período de nómina con fechas calendario
            // contra el que se descuentan las cuotas. Freelance se paga por evento, sin período fijo.
            'colaboradores' => Colaborador::whereIn('tipo', ['COLABORADOR BASE', 'CONDUCTOR', 'CONDUCTOR BASE'])
                ->orderBy('apellidos')
                ->get(['id', 'nombre', 'apellidos', 'tipo']),
        ]);
    }

    public function store(StorePrestamoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var Colaborador $colaborador */
        $colaborador = Colaborador::findOrFail($validated['colaborador_id']);
        if (in_array($colaborador->tipo, [TipoColaborador::Base, TipoColaborador::Conductor, TipoColaborador::ConductorBase], true)) {
            DB::transaction(function () use ($validated) {
                $prestamo = Prestamo::create($validated);
                $this->generarCuotas($prestamo);
            });

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Préstamo registrado y calendario de cuotas generado.']);

            return back();
        }

        return back()->withErrors(['colaborador_id' => 'Los préstamos solo aplican a colaboradores Base, Conductores o Conductores base.']);
    }

    public function destroy(Prestamo $prestamo): RedirectResponse
    {
        if ($prestamo->cuotas()->where('estado', 'PAGADA')->exists()) {
            return back()->withErrors(['delete' => 'El préstamo ya tiene cuotas pagadas y no puede eliminarse.']);
        }

        $prestamo->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Préstamo eliminado.']);

        return back();
    }

    /**
     * Pago manual de UN plazo, independiente del descuento automático en nómina — para cuando
     * el colaborador liquida la cuota directamente (efectivo, transferencia, etc.), a elección
     * del admin sobre cuál plazo registrar, sin importar el orden.
     */
    public function pagarCuota(PrestamoCuota $cuota): RedirectResponse
    {
        if ($cuota->estado === EstadoCuota::Pagada) {
            return back()->withErrors(['pago' => 'Esta cuota ya está pagada.']);
        }

        $cuota->update(['estado' => 'PAGADA', 'fecha_pago' => now()->toDateString()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cuota marcada como pagada.']);

        return back();
    }

    /**
     * Deshace un pago manual (error de captura). Solo aplica a cuotas pagadas manualmente
     * (`historico_nomina_id` nulo) — una cuota cobrada vía nómina ya pagada no se puede revertir
     * aquí, porque desincronizaría el total_final ya congelado de esa nómina.
     */
    public function revertirCuota(PrestamoCuota $cuota): RedirectResponse
    {
        if ($cuota->estado !== EstadoCuota::Pagada) {
            return back()->withErrors(['revertir' => 'Esta cuota no está pagada.']);
        }

        if ($cuota->historico_nomina_id !== null) {
            return back()->withErrors(['revertir' => 'Esta cuota se pagó a través de una nómina y no puede revertirse aquí — elimina o revierte esa nómina desde Historial.']);
        }

        $cuota->update(['estado' => 'PENDIENTE', 'fecha_pago' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pago revertido. La cuota vuelve a estar pendiente.']);

        return back();
    }

    /**
     * Aplazar (cambiar la fecha) de una o varias cuotas pendientes. Se usa desde la vista previa
     * del Panel de Validación (para sacar un plazo de la nómina en curso) y desde la gestión
     * completa de Préstamos. Solo cuotas PENDIENTE y sin ligar aún a una nómina guardada
     * (`historico_nomina_id` nulo): si ya están dentro de un cálculo congelado, el admin debe
     * eliminar/recalcular esa nómina primero.
     */
    public function aplazar(AplazarCuotasRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ids = array_map('intval', $validated['cuota_ids']);
        $cuotas = PrestamoCuota::whereIn('id', $ids)->get();

        if ($cuotas->count() !== count($ids)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Alguna cuota seleccionada no existe.']);

            return back();
        }

        if ($cuotas->contains(fn ($c) => $c->estado !== EstadoCuota::Pendiente)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Solo se pueden aplazar cuotas pendientes.']);

            return back();
        }

        if ($cuotas->contains(fn ($c) => $c->historico_nomina_id !== null)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hay cuotas ya incluidas en una nómina guardada — elimina o recalcula esa nómina primero.']);

            return back();
        }

        $fecha = Carbon::parse($validated['nueva_fecha'])->format('Y-m-d');
        $cuotas->each(fn ($c) => $c->update(['fecha_programada' => $fecha]));

        $mensaje = count($ids) === 1
            ? 'Plazo reprogramado al '.Carbon::parse($fecha)->format('d/m/Y').'.'
            : count($ids).' plazos reprogramados al '.Carbon::parse($fecha)->format('d/m/Y').'.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $mensaje]);

        return back();
    }

    /**
     * Distribuir la carga de una o varias cuotas elegidas entre TODAS las demás cuotas pendientes
     * del mismo préstamo (las que persisten). Los plazos elegidos se eliminan y su monto se SUMA
     * por partes iguales al monto de cada plazo que persiste (ajustando centavos en el último), de
     * modo que el saldo total del préstamo no cambia. Aplica solo a cuotas PENDIENTE y sin ligar a
     * una nómina guardada.
     */
    public function distribuir(DistribuirCuotasRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ids = array_map('intval', $validated['cuota_ids']);
        $seleccionadas = PrestamoCuota::with('prestamo:id,concepto')
            ->whereIn('id', $ids)
            ->orderBy('numero_plazo')
            ->get();

        if ($seleccionadas->count() !== count($ids)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Alguna cuota seleccionada no existe.']);

            return back();
        }

        if ($seleccionadas->unique('prestamo_id')->count() !== 1) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'La distribución de la carga debe hacerse dentro de un mismo préstamo.']);

            return back();
        }

        if ($seleccionadas->contains(fn ($c) => $c->estado !== EstadoCuota::Pendiente || $c->historico_nomina_id !== null)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Solo se pueden distribuir cuotas pendientes y sin incluir aún en una nómina guardada.']);

            return back();
        }

        $prestamoId = $seleccionadas->first()->prestamo_id;

        // Los plazos que "persisten": todas las demás cuotas pendientes libres del mismo préstamo.
        $restantes = PrestamoCuota::where('prestamo_id', $prestamoId)
            ->where('estado', 'PENDIENTE')
            ->whereNull('historico_nomina_id')
            ->whereNotIn('id', $ids)
            ->orderBy('numero_plazo')
            ->get();

        if ($restantes->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No quedan otros plazos pendientes para repartir la carga.']);

            return back();
        }

        $carga = (float) $seleccionadas->sum('monto');
        $n = $restantes->count();
        $adicionalBase = round($carga / $n, 2);

        DB::transaction(function () use ($restantes, $ids, $adicionalBase, $carga, $n) {
            $restantes->each(function ($c, $i) use ($adicionalBase, $carga, $n) {
                // La carga de los plazos elegidos se SUMA al monto de cada plazo que persiste
                // (los restantes conservan su propio monto y absorben una parte de la carga),
                // ajustando los centavos en el último para que el reparto cuadre exacto.
                $adicional = $i === $n - 1 ? round($carga - $adicionalBase * ($n - 1), 2) : $adicionalBase;
                $c->update(['monto' => (string) round((float) $c->monto + $adicional, 2)]);
            });

            PrestamoCuota::whereIn('id', $ids)->delete();
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Carga de '.count($ids).' plazo(s) distribuida por igual entre '.$n.' plazo(s) restantes.',
        ]);

        return back();
    }

    /**
     * Genera el calendario de cuotas: monto = monto_total / num_plazos, ajustando la última
     * cuota para que la suma cuadre exacto con monto_total (evita arrastre de centavos por
     * redondeo). Fechas: SEMANAL/QUINCENAL suman días fijos (7/15); MENSUAL usa addMonths()
     * para respetar meses calendario reales (28-31 días).
     */
    private function generarCuotas(Prestamo $prestamo): void
    {
        $montoCuota = round((float) $prestamo->monto_total / $prestamo->num_plazos, 2);
        $montoUltima = round((float) $prestamo->monto_total - ($montoCuota * ($prestamo->num_plazos - 1)), 2);
        $fechaInicio = Carbon::parse($prestamo->fecha_inicio);

        for ($n = 1; $n <= $prestamo->num_plazos; $n++) {
            $fechaPlazo = match ($prestamo->periodicidad) {
                PeriodicidadPrestamo::Semanal => $fechaInicio->copy()->addWeeks($n - 1),
                PeriodicidadPrestamo::Quincenal => $fechaInicio->copy()->addDays(15 * ($n - 1)),
                PeriodicidadPrestamo::Mensual => $fechaInicio->copy()->addMonths($n - 1),
            };

            PrestamoCuota::create([
                'prestamo_id' => $prestamo->id,
                'numero_plazo' => $n,
                'monto' => $n === $prestamo->num_plazos ? $montoUltima : $montoCuota,
                'fecha_programada' => $fechaPlazo->format('Y-m-d'),
                'estado' => 'PENDIENTE',
            ]);
        }
    }
}
