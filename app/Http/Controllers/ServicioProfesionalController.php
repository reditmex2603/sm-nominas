<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\ServicioProfesional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'     => 'required|string|max:255',
            'apellidos'  => 'nullable|string|max:255',
            'tipo'       => 'required|in:RIGGER,OPERADOR_AUDIO,OPERADOR_VIDEO,OPERADOR_LUZ,OTRO',
            'evento_id'  => 'nullable|exists:eventos,id',
            'concepto'   => 'required|string|max:500',
            'monto'      => 'required|numeric|min:0',
            'fecha'      => 'required|date',
            'autoriza'   => 'nullable|string|max:255',
        ]);

        ServicioProfesional::create($validated);

        return back();
    }
}
