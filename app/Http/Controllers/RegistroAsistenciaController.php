<?php

namespace App\Http\Controllers;

use App\Enums\TipoColaborador;
use App\Http\Requests\StoreRegistroAsistenciaRequest;
use App\Http\Requests\UpdateRegistroAsistenciaRequest;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\RegistroNormalizado;
use App\Models\TransporteDistancia;
use App\Models\TransporteUnidad;
use App\Models\TransporteVehiculo;
use App\Support\Documentos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RegistroAsistenciaController extends Controller
{
    public function index(): Response
    {
        // ── Pre-cargas para determinar protección de cada registro ────────

        // (colaborador_id|fecha) cuya jornada ya está validada
        $jornadasValidadas = JornadaConsolidada::where('validado', true)
            ->get(['colaborador_id', 'fecha'])
            ->mapWithKeys(fn ($j) => [$j->colaborador_id.'|'.$j->fecha->format('Y-m-d') => true]);

        // Nóminas BASE/CONDUCTOR (tienen rango de fechas)
        $nominasPeriodo = HistoricoNomina::whereNotNull('periodo_inicio')
            ->whereNotNull('periodo_fin')
            ->get(['colaborador_id', 'periodo_inicio', 'periodo_fin'])
            ->groupBy('colaborador_id');

        // Nóminas FREELANCE (vinculadas a evento_id)
        $nominasFreelance = HistoricoNomina::where('tipo_colaborador', 'FREELANCE')
            ->whereNotNull('evento_id')
            ->get(['colaborador_id', 'evento_id'])
            ->groupBy('colaborador_id');

        // Mapa nombre de evento → id (para registros freelance con evento_raw)
        $eventoNombreAId = Evento::pluck('id', 'nombre');

        $enNomina = function (RegistroNormalizado $r) use ($nominasPeriodo, $nominasFreelance, $eventoNombreAId): bool {
            $tipo = $r->colaborador->tipo;
            $fecha = $r->fecha->format('Y-m-d');

            if ($tipo === TipoColaborador::Freelance) {
                if (! $r->evento_raw) {
                    return false;
                }
                $eventoId = $eventoNombreAId->get($r->evento_raw);
                if (! $eventoId) {
                    return false;
                }

                return ($nominasFreelance->get($r->colaborador_id) ?? collect())
                    ->contains(fn ($n) => $n->evento_id === $eventoId);
            }

            return ($nominasPeriodo->get($r->colaborador_id) ?? collect())
                ->contains(fn ($n) => $n->periodo_inicio->format('Y-m-d') <= $fecha &&
                    $n->periodo_fin->format('Y-m-d') >= $fecha
                );
        };

        $registros = RegistroNormalizado::with(['colaborador:id,nombre,apellidos,tipo', 'unidad:id,marca,modelo,numero_placas'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get()
            ->map(fn ($r) => array_merge($r->toArray(), [
                'evidencia_url' => Documentos::url($r->evidencia_path),
                'jornada_validada' => $jornadasValidadas->has(
                    $r->colaborador_id.'|'.$r->fecha->format('Y-m-d')
                ),
                'en_nomina' => $enNomina($r),
            ]));

        return Inertia::render('registro-asistencia/Index', [
            'registros' => $registros,
            'colaboradores' => Colaborador::with('eventos:id,nombre')->orderBy('apellidos')->get(['id', 'nombre', 'apellidos', 'tipo']),
            'eventos' => Evento::orderBy('nombre')->get(['id', 'nombre']),
            'vehiculos' => TransporteVehiculo::orderBy('orden')->get(['id', 'nombre']),
            'distancias' => TransporteDistancia::orderBy('orden')->get(['id', 'nombre', 'es_standby']),
            // Unidades físicas de la flotilla — el formulario filtra por la categoría de
            // vehículo elegida (transporte_vehiculo_id).
            'unidades' => TransporteUnidad::orderBy('marca')->get(['id', 'marca', 'modelo', 'numero_placas', 'transporte_vehiculo_id']),
        ]);
    }

    public function store(StoreRegistroAsistenciaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('evidencia')) {
            $validated['evidencia_path'] = $request->file('evidencia')->store('evidencias', 'documentos');
        }
        unset($validated['evidencia']);

        RegistroNormalizado::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Registro de asistencia creado.']);

        return back();
    }

    public function update(UpdateRegistroAsistenciaRequest $request, RegistroNormalizado $registro): RedirectResponse
    {
        $validated = $request->validated();

        $registro->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Registro de asistencia actualizado.']);

        return back();
    }

    public function destroy(RegistroNormalizado $registro): RedirectResponse
    {
        $jornada = JornadaConsolidada::where('colaborador_id', $registro->colaborador_id)
            ->whereDate('fecha', $registro->fecha)
            ->first();

        if ($jornada) {
            if ($jornada->validado) {
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'No se puede eliminar: la jornada de ese día ya fue validada.',
                ]);

                return back();
            }

            if ($this->jornadaEnNomina($jornada)) {
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'No se puede eliminar: la jornada de ese día ya forma parte de una nómina guardada.',
                ]);

                return back();
            }
        }

        if ($registro->evidencia_path) {
            Storage::disk('documentos')->delete($registro->evidencia_path);
        }

        $registro->delete();

        // Si ya no quedan registros para esa (colaborador, fecha), eliminar la jornada
        $quedan = RegistroNormalizado::where('colaborador_id', $registro->colaborador_id)
            ->whereDate('fecha', $registro->fecha)
            ->exists();

        if (! $quedan) {
            $jornada?->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Registro de asistencia eliminado.']);

        return back();
    }

    private function jornadaEnNomina(JornadaConsolidada $jornada): bool
    {
        $colaborador = Colaborador::withTrashed()->find($jornada->colaborador_id, ['id', 'tipo']);
        if (! $colaborador) {
            return false;
        }

        if ($colaborador->tipo === TipoColaborador::Freelance) {
            if (! preg_match('/^Evento: (.+?) - /m', $jornada->detalle ?? '', $m)) {
                return false;
            }
            $eventoId = Evento::where('nombre', $m[1])->value('id');
            if (! $eventoId) {
                return false;
            }

            return HistoricoNomina::where('colaborador_id', $colaborador->id)
                ->where('evento_id', $eventoId)
                ->exists();
        }

        return HistoricoNomina::where('colaborador_id', $colaborador->id)
            ->where('periodo_inicio', '<=', $jornada->fecha)
            ->where('periodo_fin', '>=', $jornada->fecha)
            ->exists();
    }
}
