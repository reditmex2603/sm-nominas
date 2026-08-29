<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateParametrosRequest;
use App\Models\ParametroSistema;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ParametroController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('parametros/Index', [
            'parametros' => ParametroSistema::all()->keyBy('clave'),
        ]);
    }

    public function update(UpdateParametrosRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated as $clave => $valor) {
            ParametroSistema::where('clave', $clave)->update(['valor' => $valor]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Parámetros guardados correctamente.']);

        return back();
    }
}
