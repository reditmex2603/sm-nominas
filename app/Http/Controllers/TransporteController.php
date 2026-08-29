<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransporteRequest;
use App\Models\TransporteDistancia;
use App\Models\TransporteTarifa;
use App\Models\TransporteUnidad;
use App\Models\TransporteVehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransporteController extends Controller
{
    public function index(): Response
    {
        $vehiculos = TransporteVehiculo::orderBy('orden')->get();
        $distancias = TransporteDistancia::orderBy('orden')->get();

        $tarifaMap = [];
        TransporteTarifa::all()->each(function ($t) use (&$tarifaMap) {
            $tarifaMap[$t->vehiculo_id][$t->distancia_id] = $t->tarifa;
        });

        return Inertia::render('transportes/Index', [
            'vehiculos' => $vehiculos,
            'distancias' => $distancias,
            'tarifas' => $tarifaMap,
            'unidades' => TransporteUnidad::with('vehiculo:id,nombre')->orderBy('marca')->get(),
        ]);
    }

    public function guardar(StoreTransporteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {
                TransporteTarifa::query()->delete();
                TransporteVehiculo::query()->delete();
                TransporteDistancia::query()->delete();

                foreach ($validated['vehiculos'] as $i => $v) {
                    TransporteVehiculo::create(['nombre' => $v['nombre'], 'orden' => $i]);
                }

                foreach ($validated['distancias'] as $i => $d) {
                    TransporteDistancia::create([
                        'nombre' => $d['nombre'],
                        'es_standby' => $d['es_standby'] ?? false,
                        'orden' => $i,
                    ]);
                }

                $vehiculosMap = TransporteVehiculo::all()->keyBy('nombre');
                $distanciasMap = TransporteDistancia::all()->keyBy('nombre');

                foreach ($validated['tarifas'] as $vNombre => $distanciaTarifas) {
                    $vehiculoId = $vehiculosMap[$vNombre]?->id;
                    if (! $vehiculoId) {
                        continue;
                    }

                    foreach ($distanciaTarifas as $dNombre => $tarifa) {
                        $distanciaId = $distanciasMap[$dNombre]?->id;
                        if (! $distanciaId) {
                            continue;
                        }

                        TransporteTarifa::create([
                            'vehiculo_id' => $vehiculoId,
                            'distancia_id' => $distanciaId,
                            'tarifa' => is_numeric($tarifa) ? $tarifa : 0,
                        ]);
                    }
                }
            });
        } catch (\Throwable) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se pudieron guardar las tarifas. Inténtalo de nuevo.']);

            return back();
        }

        return back();
    }

    public function eliminarDistancia(TransporteDistancia $distancia): RedirectResponse
    {
        if (TransporteDistancia::count() <= 1) {
            return back()->withErrors(['delete' => 'Debe quedar al menos una distancia.']);
        }

        $distancia->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Distancia eliminada.']);

        return back();
    }

    public function eliminarVehiculo(TransporteVehiculo $vehiculo): RedirectResponse
    {
        if (TransporteVehiculo::count() <= 1) {
            return back()->withErrors(['delete' => 'Debe quedar al menos un vehículo.']);
        }

        $vehiculo->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehículo eliminado.']);

        return back();
    }
}
