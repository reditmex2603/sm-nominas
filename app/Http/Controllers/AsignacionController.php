<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncAsignacionesRequest;
use App\Models\Evento;
use Illuminate\Http\RedirectResponse;

class AsignacionController extends Controller
{
    public function sync(SyncAsignacionesRequest $request, Evento $evento): RedirectResponse
    {
        $validated = $request->validated();

        $evento->colaboradores()->sync($validated['colaborador_ids']);

        return back();
    }
}
