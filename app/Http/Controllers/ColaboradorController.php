<?php

namespace App\Http\Controllers;

use App\Enums\TipoColaborador;
use App\Http\Requests\StoreColaboradorRequest;
use App\Http\Requests\UpdateColaboradorNominaRequest;
use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\ColaboradorPerfil;
use App\Models\HistoricoNomina;
use App\Models\Prestamo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ColaboradorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('colaboradores/Index', [
            'colaboradores' => Colaborador::with('perfil')
                ->orderBy('apellidos')
                ->orderBy('nombre')
                ->get()
                ->map(function (Colaborador $colaborador) {
                    $data = $colaborador->only([
                        'id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel', 'area',
                        'compensacion_pct', 'sueldo_diario', 'extra_dia_adicional', 'token',
                    ]);

                    $data['perfil_pendiente'] = $this->perfilPendiente($colaborador->perfil);

                    return $data;
                })
                ->values(),
        ]);
    }

    /** El perfil está pendiente si no existe o está vacío (sin NSS ni documentos ni datos obligatorios de contacto). */
    private function perfilPendiente(?ColaboradorPerfil $perfil): bool
    {
        if ($perfil === null) {
            return true;
        }

        return blank($perfil->numero_seguro_social)
            && blank($perfil->tipo_sangre)
            && blank($perfil->alergias)
            && blank($perfil->padecimientos_cronicos)
            && blank($perfil->seguro_social_documento_path)
            && blank($perfil->ine_documento_path)
            && blank($perfil->curp_documento_path)
            && blank($perfil->comprobante_domicilio_documento_path)
            && blank($perfil->licencia_conducir_documento_path)
            && blank($perfil->fecha_ingreso)
            && blank($perfil->telefono)
            && blank($perfil->whatsapp);
    }

    public function store(StoreColaboradorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Colaborador::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Colaborador creado correctamente.']);

        return back();
    }

    public function update(UpdateColaboradorNominaRequest $request, Colaborador $colaborador): RedirectResponse
    {
        // Si el tipo no tiene campos de nómina editables (reglas vacías), no hay nada que actualizar.
        if (empty($request->rules())) {
            return back();
        }

        $colaborador->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Datos de nómina del colaborador actualizados.']);

        return back();
    }

    public function regenerarToken(Colaborador $colaborador): RedirectResponse
    {
        $colaborador->forceFill(['token' => (string) Str::uuid()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Enlace de asistencia regenerado.']);

        return back();
    }

    /**
     * Panel "Historial" de un colaborador: todos sus movimientos en el sistema de nóminas
     * (nóminas con desglose, anticipos y préstamos con su calendario de cuotas).
     */
    public function historial(Colaborador $colaborador): Response
    {
        $colaborador->load('perfil');

        return Inertia::render('colaboradores/Historial', [
            'colaborador' => $colaborador->only([
                'id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel', 'area',
                'sueldo_diario', 'extra_dia_adicional', 'compensacion_pct',
            ]),
            'perfil' => $colaborador->perfil ? [
                'telefono' => $colaborador->perfil->telefono,
                'whatsapp' => $colaborador->perfil->whatsapp,
            ] : null,
            'nominas' => HistoricoNomina::where('colaborador_id', $colaborador->id)
                ->orderByDesc('periodo_inicio')
                ->orderByDesc('fecha_calculo')
                ->get(),
            'anticipos' => Anticipo::where('colaborador_id', $colaborador->id)
                ->with('evento:id,nombre')
                ->orderByDesc('fecha')
                ->get(),
            'prestamos' => Prestamo::where('colaborador_id', $colaborador->id)
                ->with('cuotas')
                ->orderByDesc('fecha_inicio')
                ->get(),
        ]);
    }

    public function destroy(Colaborador $colaborador): RedirectResponse
    {
        $bloqueos = [];

        if ($colaborador->nominas()->where('estado', 'PENDIENTE')->exists()) {
            $bloqueos[] = 'nóminas pendientes';
        }

        if ($colaborador->prestamos()->whereHas('cuotas', fn ($q) => $q->where('estado', 'PENDIENTE'))->exists()) {
            $bloqueos[] = 'cuotas de préstamo pendientes de cobro';
        }

        $anticipoSinDescontar = in_array($colaborador->tipo, [TipoColaborador::Base, TipoColaborador::Conductor, TipoColaborador::ConductorBase], true)
            && Anticipo::where('colaborador_id', $colaborador->id)
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('historico_nomina')
                        ->whereColumn('historico_nomina.colaborador_id', 'anticipos.colaborador_id')
                        ->where('historico_nomina.estado', 'PAGADO')
                        ->whereNotNull('historico_nomina.periodo_inicio')
                        ->whereNotNull('historico_nomina.periodo_fin')
                        ->whereColumn('historico_nomina.periodo_inicio', '<=', 'anticipos.fecha')
                        ->whereColumn('historico_nomina.periodo_fin', '>=', 'anticipos.fecha');
                })
                ->exists();

        if ($anticipoSinDescontar) {
            $bloqueos[] = 'anticipos que aún no se han descontado en una nómina pagada';
        }

        if (! empty($bloqueos)) {
            return back()->withErrors([
                'delete' => 'El colaborador tiene '.implode(', ', $bloqueos).' y no puede eliminarse.',
            ]);
        }

        $colaborador->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Colaborador eliminado.']);

        return back();
    }
}
