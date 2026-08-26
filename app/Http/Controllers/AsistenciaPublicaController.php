<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\RegistroNormalizado;
use App\Models\TransporteDistancia;
use App\Models\TransporteUnidad;
use App\Models\TransporteVehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AsistenciaPublicaController extends Controller
{
    public function show(string $token): Response
    {
        $colaborador = Colaborador::where('token', $token)->firstOrFail();

        return Inertia::render('asistencia-publica/Show', [
            'colaborador' => [
                'id' => $colaborador->id,
                'nombre' => $colaborador->nombre,
                'apellidos' => $colaborador->apellidos,
                'tipo' => $colaborador->tipo,
            ],
            'eventos' => $colaborador->eventos()->orderBy('nombre')->get(['eventos.id', 'nombre']),
            'vehiculos' => TransporteVehiculo::orderBy('orden')->get(['id', 'nombre']),
            'distancias' => TransporteDistancia::orderBy('orden')->get(['id', 'nombre', 'es_standby']),
            // Unidades físicas de la flotilla — el formulario filtra por la categoría de
            // vehículo elegida (transporte_vehiculo_id).
            'unidades' => TransporteUnidad::orderBy('marca')->get(['id', 'marca', 'modelo', 'numero_placas', 'transporte_vehiculo_id']),
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $colaborador = Colaborador::where('token', $token)->firstOrFail();

        $tiposPermitidos = match ($colaborador->tipo) {
            'COLABORADOR BASE' => ['Bodega', 'Evento'],
            'FREELANCE' => ['Evento'],
            'CONDUCTOR' => ['Transporte'],
            'CONDUCTOR BASE' => ['Bodega', 'Transporte'],
            default => [],
        };

        $validated = $request->validate([
            'tipo_actividad' => ['required', Rule::in($tiposPermitidos)],
            'actividad' => ['nullable', 'required_if:tipo_actividad,Bodega', 'string', 'max:500'],
            'evento_raw' => ['nullable', 'required_if:tipo_actividad,Evento', 'string', 'max:255'],
            'etapa' => 'nullable|string|max:100',
            'vehiculo' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'distancia' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'transporte_unidad_id' => ['nullable', 'required_if:tipo_actividad,Transporte', 'exists:transporte_unidades,id'],
            'origen' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'destino' => ['nullable', 'required_if:tipo_actividad,Transporte', 'string', 'max:255'],
            'extras' => 'nullable|string|max:2000',
            'evidencia' => 'required|file|image|max:5120',
            'comentarios' => 'nullable|string|max:1000',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
        ]);

        if ($request->hasFile('evidencia')) {
            $validated['evidencia_path'] = $request->file('evidencia')->store('evidencias', 'documentos');
        }
        unset($validated['evidencia']);

        $validated['colaborador_id'] = $colaborador->id;

        RegistroNormalizado::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Registro enviado correctamente.']);

        return back();
    }
}
