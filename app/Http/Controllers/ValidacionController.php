<?php

namespace App\Http\Controllers;

use App\Enums\TipoColaborador;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\RegistroNormalizado;
use App\Support\Documentos;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ValidacionController extends Controller
{
    public function index(): Response
    {
        // BASE + CONDUCTOR: nóminas con rango de fechas
        $nominasPeriodo = HistoricoNomina::whereNotNull('periodo_inicio')
            ->whereNotNull('periodo_fin')
            ->get(['colaborador_id', 'periodo_inicio', 'periodo_fin', 'estado'])
            ->groupBy('colaborador_id');

        // FREELANCE: nóminas vinculadas a evento_id
        $nominasFreelance = HistoricoNomina::where('tipo_colaborador', 'FREELANCE')
            ->whereNotNull('evento_id')
            ->get(['colaborador_id', 'evento_id', 'estado'])
            ->groupBy('colaborador_id');

        // Mapa nombre_evento → id para parsear el detalle de jornadas freelance
        $eventoNombreAId = Evento::pluck('id', 'nombre');

        $nominaEstado = function ($j) use ($nominasPeriodo, $nominasFreelance, $eventoNombreAId): ?string {
            if ($j->colaborador->tipo === TipoColaborador::Freelance) {
                // Extrae el nombre del evento del detalle ("Evento: Nombre - Etapa")
                if (! preg_match('/^Evento: (.+?) - /m', $j->detalle ?? '', $m)) {
                    return null;
                }
                $eventoId = $eventoNombreAId->get($m[1]);
                if (! $eventoId) {
                    return null;
                }

                return ($nominasFreelance->get($j->colaborador_id) ?? collect())
                    ->first(fn ($n) => $n->evento_id === $eventoId)
                    ?->estado;
            }

            return ($nominasPeriodo->get($j->colaborador_id) ?? collect())
                ->first(fn ($n) => $n->periodo_inicio->format('Y-m-d') <= $j->fecha->format('Y-m-d')
                    && $n->periodo_fin->format('Y-m-d') >= $j->fecha->format('Y-m-d')
                )?->estado;
        };

        $jornadas = JornadaConsolidada::with('colaborador:id,nombre,apellidos,tipo,sueldo_diario,compensacion_pct')
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn ($j) => array_merge($j->toArray(), [
                'evidencia_urls' => collect(explode(',', $j->evidencias ?? ''))
                    ->map(fn ($p) => trim($p))
                    ->filter()
                    ->map(fn ($p) => Documentos::url($p))
                    ->values()
                    ->toArray(),
                'nomina_estado' => $nominaEstado($j),
                // Eventos detectados ese día (puede haber más de uno) — para elegir la
                // fracción (100%/Traslape 50%/Traslape 40%) de cada uno por separado.
                'eventos_dia' => Evento::extraerDeDetalle($j->detalle ?? '')
                    ->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre, 'tamano' => $e->tamano])
                    ->values(),
            ]));

        // Registros que aún no tienen jornada consolidada para esa (colaborador, fecha)
        $registrosSinProcesar = RegistroNormalizado::whereNotExists(
            fn ($q) => $q->select(DB::raw(1))
                ->from('jornadas_consolidadas')
                ->whereColumn('jornadas_consolidadas.colaborador_id', 'registros_normalizados.colaborador_id')
                ->whereColumn('jornadas_consolidadas.fecha', 'registros_normalizados.fecha')
        )->count();

        return Inertia::render('validacion/Index', [
            'jornadas' => $jornadas,
            'colaboradores' => Colaborador::orderBy('apellidos')->get(['id', 'nombre', 'apellidos', 'tipo']),
            'registrosSinProcesar' => $registrosSinProcesar,
        ]);
    }
}
