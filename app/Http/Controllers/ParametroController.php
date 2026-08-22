<?php

namespace App\Http\Controllers;

use App\Models\ParametroSistema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pago_default_chico' => 'required|numeric|min:0',
            'pago_default_mediano' => 'required|numeric|min:0',
            'pago_default_grande' => 'required|numeric|min:0',
            'dias_bono_septimo' => 'required|integer|min:1|max:6',
            'bono_evento_encargado_nivel1_mediano' => 'required|numeric|min:0',
            'bono_evento_encargado_nivel1_grande' => 'required|numeric|min:0',
            'bono_evento_encargado_nivel2_mediano' => 'required|numeric|min:0',
            'bono_evento_encargado_nivel2_grande' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel1_mediano' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel1_grande' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel2_mediano' => 'required|numeric|min:0',
            'bono_evento_tecnico_nivel2_grande' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel1_mediano' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel1_grande' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel2_mediano' => 'required|numeric|min:0',
            'bono_evento_stagehand_nivel2_grande' => 'required|numeric|min:0',
        ]);

        foreach ($validated as $clave => $valor) {
            ParametroSistema::where('clave', $clave)->update(['valor' => $valor]);
        }

        return back();
    }
}
