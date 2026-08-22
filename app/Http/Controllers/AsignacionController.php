<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AsignacionController extends Controller
{
    public function sync(Request $request, Evento $evento): RedirectResponse
    {
        $validated = $request->validate([
            'colaborador_ids' => 'present|array',
            'colaborador_ids.*' => 'integer|exists:colaboradores,id',
        ]);

        $evento->colaboradores()->sync($validated['colaborador_ids']);

        return back();
    }
}
