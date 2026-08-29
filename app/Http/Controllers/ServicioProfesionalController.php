<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicioProfesionalRequest;
use App\Models\Evento;
use App\Models\ServicioProfesional;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServicioProfesionalController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('servicios-profesionales/Index', [
            'servicios' => ServicioProfesional::with('evento:id,nombre')
                ->orderBy('fecha', 'desc')
                ->get(),
            'eventos' => Evento::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(StoreServicioProfesionalRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ServicioProfesional::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio profesional registrado correctamente.']);

        return back();
    }
}
