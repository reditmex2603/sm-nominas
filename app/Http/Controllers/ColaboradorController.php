<?php

namespace App\Http\Controllers;

use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\ColaboradorPerfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                        'id', 'nombre', 'apellidos', 'tipo', 'categoria', 'nivel',
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'tipo' => 'required|in:COLABORADOR BASE,FREELANCE,CONDUCTOR,CONDUCTOR BASE',
            'categoria' => 'required_if:tipo,COLABORADOR BASE|nullable|in:Encargado de área,Técnico,Stagehand SM',
            'nivel' => 'required_if:tipo,COLABORADOR BASE|nullable|integer|in:1,2',
            'compensacion_pct' => 'nullable|integer|min:0|max:100',
            'sueldo_diario' => 'required_if:tipo,CONDUCTOR BASE|nullable|numeric|min:0',
            'extra_dia_adicional' => 'nullable|numeric|min:0',
        ]);

        Colaborador::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Colaborador creado correctamente.']);

        return back();
    }

    public function update(Request $request, Colaborador $colaborador): RedirectResponse
    {
        $rules = [];

        if ($colaborador->tipo === 'COLABORADOR BASE') {
            $rules['sueldo_diario'] = 'nullable|numeric|min:0';
            $rules['categoria'] = 'required|in:Encargado de área,Técnico,Stagehand SM';
            $rules['nivel'] = 'required|integer|in:1,2';
            $rules['compensacion_pct'] = 'nullable|integer|min:0|max:100';
        }

        if ($colaborador->tipo === 'CONDUCTOR BASE') {
            $rules['sueldo_diario'] = 'required|numeric|min:0';
            $rules['compensacion_pct'] = 'nullable|integer|min:0|max:100';
        }

        if ($colaborador->tipo === 'FREELANCE') {
            $rules['extra_dia_adicional'] = 'nullable|numeric|min:0';
        }

        if (empty($rules)) {
            return back();
        }

        $colaborador->update($request->validate($rules));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Datos de nómina del colaborador actualizados.']);

        return back();
    }

    public function regenerarToken(Colaborador $colaborador): RedirectResponse
    {
        $colaborador->forceFill(['token' => (string) Str::uuid()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Enlace de asistencia regenerado.']);

        return back();
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

        $anticipoSinDescontar = in_array($colaborador->tipo, ['COLABORADOR BASE', 'CONDUCTOR', 'CONDUCTOR BASE'], true)
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
