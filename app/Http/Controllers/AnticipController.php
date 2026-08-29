<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnticipoRequest;
use App\Models\Anticipo;
use App\Models\Asignacion;
use App\Models\Colaborador;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnticipController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('anticipos/Index', [
            'anticipos' => Anticipo::with('colaborador:id,nombre,apellidos,tipo', 'evento:id,nombre')
                ->orderBy('fecha', 'desc')
                ->get(),
            'colaboradores' => Colaborador::orderBy('apellidos')->get(['id', 'nombre', 'apellidos', 'tipo']),
            'colaboradores_eventos' => Asignacion::with('evento:id,nombre')->get()
                ->groupBy('colaborador_id')
                ->map(fn ($g) => $g->pluck('evento')->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre])->values()),
        ]);
    }

    public function store(StoreAnticipoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Default: fecha de hoy si no se proporciona
        $validated['fecha'] ??= now()->toDateString();
        $validated['tipo'] ??= 'SUELTO';

        if ($validated['tipo'] !== 'EVENTO') {
            $validated['evento_id'] = null;
        }

        Anticipo::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anticipo registrado correctamente.']);

        return back();
    }
}
