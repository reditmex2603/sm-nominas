<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoricoController extends Controller
{
    /** Columnas del perfil de colaborador que se exponen en las nóminas (datos bancarios para pago). */
    private const PERFIL_BANCARIO_COLUMNS = 'colaborador_id,banco,beneficiario,clave_interbancaria';

    public function index(): Response
    {
        return Inertia::render('historial/Index', [
            'nominas_base' => HistoricoNomina::with([
                'colaborador:id,nombre,apellidos',
                'colaborador.perfil:'.self::PERFIL_BANCARIO_COLUMNS,
            ])
                ->where('tipo_colaborador', 'COLABORADOR BASE')
                ->orderBy('periodo_inicio', 'desc')
                ->get(),

            'nominas_freelance' => HistoricoNomina::with([
                'colaborador:id,nombre,apellidos',
                'colaborador.perfil:'.self::PERFIL_BANCARIO_COLUMNS,
            ])
                ->where('tipo_colaborador', 'FREELANCE')
                ->orderByDesc('created_at')
                ->get(),

            'nominas_conductores' => HistoricoNomina::with([
                'colaborador:id,nombre,apellidos',
                'colaborador.perfil:'.self::PERFIL_BANCARIO_COLUMNS,
            ])
                ->where('tipo_colaborador', 'CONDUCTOR')
                ->orderBy('periodo_inicio', 'desc')
                ->get(),

            'nominas_conductor_base' => HistoricoNomina::with([
                'colaborador:id,nombre,apellidos',
                'colaborador.perfil:'.self::PERFIL_BANCARIO_COLUMNS,
            ])
                ->where('tipo_colaborador', 'CONDUCTOR BASE')
                ->orderBy('periodo_inicio', 'desc')
                ->get(),

            'eventos' => Evento::orderBy('nombre')->get(['id', 'nombre', 'tamano']),
            'colaboradores' => Colaborador::orderBy('apellidos')->get(['id', 'nombre', 'apellidos', 'tipo']),
        ]);
    }

    public function imprimir(HistoricoNomina $nomina): Response
    {
        $nomina->load([
            'colaborador:id,nombre,apellidos',
            'colaborador.perfil:'.self::PERFIL_BANCARIO_COLUMNS,
        ]);

        return Inertia::render('historial/Imprimir', [
            'nomina' => $nomina,
        ]);
    }

    public function imprimirRango(Request $request): Response
    {
        $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'tipo' => 'nullable|in:base,freelance,conductores,conductor_base',
            'colaborador_id' => 'nullable|integer|exists:colaboradores,id',
            'estado' => 'nullable|in:PENDIENTE,PAGADO',
        ]);

        $query = HistoricoNomina::with([
            'colaborador:id,nombre,apellidos',
            'colaborador.perfil:'.self::PERFIL_BANCARIO_COLUMNS,
        ]);

        if ($request->filled('fecha_desde')) {
            $query->where('periodo_inicio', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('periodo_fin', '<=', $request->fecha_hasta);
        }
        if ($request->filled('tipo')) {
            $map = [
                'base' => 'COLABORADOR BASE',
                'freelance' => 'FREELANCE',
                'conductores' => 'CONDUCTOR',
                'conductor_base' => 'CONDUCTOR BASE',
            ];
            $query->where('tipo_colaborador', $map[$request->tipo]);
        }
        if ($request->filled('colaborador_id')) {
            $query->where('colaborador_id', $request->colaborador_id);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $nominas = $query->orderBy('periodo_inicio', 'desc')->get();

        $colaborador = $request->filled('colaborador_id')
            ? Colaborador::withTrashed()->find($request->colaborador_id)
            : null;

        return Inertia::render('historial/ImprimirRango', [
            'nominas' => $nominas,
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'tipo' => $request->tipo,
            'colaborador_id' => $request->filled('colaborador_id') ? (int) $request->colaborador_id : null,
            'colaborador_nombre' => $colaborador ? "{$colaborador->apellidos}, {$colaborador->nombre}" : null,
            'estado' => $request->estado,
        ]);
    }
}
