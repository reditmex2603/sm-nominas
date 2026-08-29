<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarCompensacionJornadaRequest;
use App\Http\Requests\ActualizarFraccionEventoJornadaRequest;
use App\Http\Requests\ActualizarTipoPagoJornadaRequest;
use App\Http\Requests\ActualizarValidadoJornadaRequest;
use App\Models\Evento;
use App\Models\JornadaConsolidada;
use App\Services\JornadaGenerator;
use Illuminate\Http\RedirectResponse;

class JornadaController extends Controller
{
    public function generar(JornadaGenerator $generator): RedirectResponse
    {
        $errores = $generator->generar();

        if (! empty($errores)) {
            return back()->with('warning', 'Jornadas generadas con '.count($errores).' advertencia(s): '.implode('; ', $errores));
        }

        return back()->with('success', 'Jornadas regeneradas correctamente.');
    }

    public function actualizarValidado(ActualizarValidadoJornadaRequest $request, JornadaConsolidada $jornada): RedirectResponse
    {
        $jornada->update(['validado' => $request->validated()['validado']]);

        return back();
    }

    public function actualizarTipoPago(ActualizarTipoPagoJornadaRequest $request, JornadaConsolidada $jornada): RedirectResponse
    {
        // Con 2+ eventos el mismo día, el traslape se pondera POR EVENTO (fracciones_evento),
        // nunca como tipo de pago de todo el día — así que TRASLAPE es inválido ahí.
        if ($request->input('tipo_pago') === 'TRASLAPE'
            && Evento::extraerDeDetalle($jornada->detalle ?? '')->count() >= 2) {
            return back()->withErrors(['tipo_pago' => 'Un día con dos o más eventos no puede marcarse como Traslape: pondera cada evento individualmente.']);
        }

        $validated = $request->validated();

        $jornada->update([
            'tipo_pago' => $validated['tipo_pago'],
            'traslape_pct' => $validated['tipo_pago'] === 'TRASLAPE' ? $validated['traslape_pct'] : null,
        ]);

        return back();
    }

    /**
     * Porcentaje individual (1-100) de UN evento específico dentro de un día con 2+ eventos —
     * el desempeño del colaborador puede variar por evento. 100 = paga completo.
     */
    public function actualizarFraccionEvento(ActualizarFraccionEventoJornadaRequest $request, JornadaConsolidada $jornada): RedirectResponse
    {
        $validated = $request->validated();

        $fracciones = $jornada->fracciones_evento ?? [];
        $fracciones[$validated['evento_id']] = $validated['porcentaje'];

        $jornada->update(['fracciones_evento' => $fracciones]);

        return back();
    }

    /**
     * Activa/desactiva la Compensación (bono extra sobre el Extra por día de evento, según el
     * % configurado en el colaborador) para esta jornada — aplica a todos los eventos del día.
     */
    public function actualizarCompensacion(ActualizarCompensacionJornadaRequest $request, JornadaConsolidada $jornada): RedirectResponse
    {
        $jornada->update(['compensacion_activa' => $request->validated()['compensacion_activa']]);

        return back();
    }
}
