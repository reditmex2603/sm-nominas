<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsistenciaPublicaRequest;
use App\Models\Colaborador;
use App\Models\RegistroNormalizado;
use App\Models\TransporteDistancia;
use App\Models\TransporteUnidad;
use App\Models\TransporteVehiculo;
use Illuminate\Http\RedirectResponse;
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

    public function store(StoreAsistenciaPublicaRequest $request, string $token): RedirectResponse
    {
        $colaborador = Colaborador::where('token', $token)->firstOrFail();

        $validated = $request->validated();

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
