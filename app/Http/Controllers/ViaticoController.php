<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Viatico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ViaticoController extends Controller
{
    /** Concepto auto-generado por la matriz — se recrea en cada guardado. */
    private const CONCEPTO_DIARIO = 'VIÁTICO DIARIO';

    public function index(): Response
    {
        return Inertia::render('viaticos/Index', [
            'viaticos' => Viatico::with(['evento:id,nombre', 'colaborador:id,nombre,apellidos'])
                ->orderBy('fecha', 'desc')
                ->get(),
            // Cada evento trae sus colaboradores asignados y su rango de fechas — la matriz de
            // viáticos se arma por colaborador × día del evento, con un gasto diario único global.
            'eventos' => Evento::with([
                'colaboradores:colaboradores.id,nombre,apellidos,tipo',
            ])
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin', 'viatico_diario']),
        ]);
    }

    public function matriz(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'evento_id' => 'required|exists:eventos,id',
            // Gasto diario único para todo el evento (no por colaborador).
            'def_diario' => 'nullable|numeric|min:0',
            'filas' => 'required|array',
            'filas.*.colaborador_id' => [
                'required',
                'integer',
                Rule::exists('asignaciones', 'colaborador_id')
                    ->where(fn ($q) => $q->where('evento_id', $request->input('evento_id'))),
            ],
            'filas.*.dias' => 'array',
            'filas.*.dias.*' => 'nullable|numeric|min:0',
        ]);

        $evento = Evento::findOrFail($validated['evento_id']);

        DB::transaction(function () use ($validated, $evento) {
            $defDiario = $validated['def_diario'] ?? null;

            // El gasto por día es una configuración única del evento.
            $evento->update(['viatico_diario' => ($defDiario === null || $defDiario === '') ? null : $defDiario]);

            foreach ($validated['filas'] as $fila) {
                $colaboradorId = $fila['colaborador_id'];

                // Recrea los viáticos diarios generados por la matriz.
                // Los extras se gestionan desde el registro manual ("Nuevo viático").
                Viatico::where('evento_id', $evento->id)
                    ->where('colaborador_id', $colaboradorId)
                    ->where('concepto', self::CONCEPTO_DIARIO)
                    ->delete();

                // Los días marcados llegan como fecha => monto. Si el monto está vacío se usa el
                // gasto global del evento; si trae valor, es un monto distinto para ese día.
                foreach ($fila['dias'] ?? [] as $dia => $montoDia) {
                    $monto = ($montoDia !== null && $montoDia !== '') ? $montoDia : $defDiario;

                    Viatico::create([
                        'evento_id' => $evento->id,
                        'colaborador_id' => $colaboradorId,
                        'nombre' => null,
                        'apellidos' => null,
                        'tipo' => 'OTRO',
                        'concepto' => self::CONCEPTO_DIARIO,
                        'monto' => $monto ?: 0,
                        'fecha' => $dia,
                    ]);
                }
            }
        });

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Modo "Por colaborador": colaborador_id (debe estar asignado al evento elegido), sin
            // nombre libre. Modo "General": nombre libre, sin colaborador_id.
            'colaborador_id' => [
                'nullable',
                Rule::exists('asignaciones', 'colaborador_id')
                    ->where(fn ($q) => $q->where('evento_id', $request->input('evento_id'))),
            ],
            'nombre' => 'required_without:colaborador_id|nullable|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'tipo' => 'required|in:TRANSPORTE,HOSPEDAJE,ALIMENTOS,CASETAS_GASOLINA,OTRO',
            'evento_id' => 'required|exists:eventos,id',
            'concepto' => 'required|string|max:500',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'autoriza' => 'nullable|string|max:255',
        ]);

        if (! empty($validated['colaborador_id'])) {
            $validated['nombre'] = null;
            $validated['apellidos'] = null;
        }

        Viatico::create($validated);

        return back();
    }
}
