<?php

namespace App\Http\Controllers;

use App\Models\Anticipo;
use App\Models\Asignacion;
use App\Models\Colaborador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'colaborador_id' => 'required|exists:colaboradores,id',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'nullable|string|max:500',
            'tipo' => 'sometimes|in:EVENTO,SUELTO',
            'evento_id' => [
                'nullable',
                'required_if:tipo,EVENTO',
                Rule::exists('asignaciones', 'evento_id')
                    ->where('colaborador_id', $request->integer('colaborador_id')),
            ],
            'fecha' => 'nullable|date',
            'entregado_por' => 'nullable|string|max:255',
        ]);

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
