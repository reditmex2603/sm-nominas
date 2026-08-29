<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarDocumentosTransporteUnidadRequest;
use App\Http\Requests\StoreTransporteUnidadRequest;
use App\Http\Requests\UpdateTransporteUnidadRequest;
use App\Models\TransporteUnidad;
use App\Models\TransporteVehiculo;
use App\Support\Documentos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TransporteUnidadController extends Controller
{
    /** Documentos de la unidad — campo de subida/eliminación → columna `{campo}_documento_path`. */
    private const CAMPOS_DOCUMENTO = ['placas', 'tarjeta_circulacion', 'poliza_seguro', 'verificacion', 'tenencia'];

    public function store(StoreTransporteUnidadRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        TransporteUnidad::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidad de transporte creada.']);

        return back();
    }

    public function update(UpdateTransporteUnidadRequest $request, TransporteUnidad $unidad): RedirectResponse
    {
        $validated = $request->validated();

        $unidad->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidad actualizada.']);

        return back();
    }

    public function destroy(TransporteUnidad $unidad): RedirectResponse
    {
        foreach (self::CAMPOS_DOCUMENTO as $campo) {
            $columna = "{$campo}_documento_path";
            if ($unidad->{$columna}) {
                Storage::disk('documentos')->delete($unidad->{$columna});
            }
        }

        if ($unidad->fotografia_path) {
            Storage::disk('documentos')->delete($unidad->fotografia_path);
        }

        $unidad->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidad eliminada.']);

        return back();
    }

    public function show(TransporteUnidad $unidad): Response
    {
        $unidad->load('vehiculo:id,nombre');

        return Inertia::render('transportes/UnidadDetalle', [
            // El cast 'date' serializa a ISO completo, que <input type="date"> no acepta como
            // value — se reformatea a "Y-m-d" (mismo gotcha resuelto en EventoController).
            'unidad' => array_merge($unidad->toArray(), $this->documentoUrls($unidad), $this->fotografiaUrl($unidad), [
                'vigencia_poliza_seguro' => $unidad->vigencia_poliza_seguro?->format('Y-m-d'),
                'vigencia_verificacion' => $unidad->vigencia_verificacion?->format('Y-m-d'),
            ]),
            'vehiculos' => TransporteVehiculo::orderBy('orden')->get(['id', 'nombre']),
        ]);
    }

    public function imprimirPerfil(TransporteUnidad $unidad): Response
    {
        $unidad->load('vehiculo:id,nombre');

        return Inertia::render('transportes/ImprimirPerfilUnidad', [
            'unidad' => array_merge($unidad->toArray(), $this->documentoUrls($unidad), $this->fotografiaUrl($unidad)),
        ]);
    }

    public function actualizarDocumentos(ActualizarDocumentosTransporteUnidadRequest $request, TransporteUnidad $unidad): RedirectResponse
    {
        $validated = $request->validated();

        $unidad->fill([
            'alias' => $validated['alias'] ?? null,
            'numero_serie' => $validated['numero_serie'] ?? null,
            'numero_poliza_seguro' => $validated['numero_poliza_seguro'] ?? null,
            'vigencia_poliza_seguro' => $validated['vigencia_poliza_seguro'] ?? null,
            'vigencia_verificacion' => $validated['vigencia_verificacion'] ?? null,
            'tipo_engomado' => $validated['tipo_engomado'] ?? null,
            'color_engomado' => $validated['color_engomado'] ?? null,
        ]);

        foreach (self::CAMPOS_DOCUMENTO as $campo) {
            $inputName = "{$campo}_documento";
            $columna = "{$campo}_documento_path";

            if ($request->hasFile($inputName)) {
                if ($unidad->{$columna}) {
                    Storage::disk('documentos')->delete($unidad->{$columna});
                }

                if ($nuevoPath = $request->file($inputName)->store('unidades-transporte', 'documentos')) {
                    $unidad->{$columna} = $nuevoPath;
                }
            }
        }

        if ($request->hasFile('fotografia')) {
            if ($unidad->fotografia_path) {
                Storage::disk('documentos')->delete($unidad->fotografia_path);
            }

            if ($nuevoPath = $request->file('fotografia')->store('unidades-flotilla', 'documentos')) {
                $unidad->fotografia_path = $nuevoPath;
            }
        }

        $unidad->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Documentos y datos de la unidad actualizados.']);

        return back();
    }

    public function eliminarDocumento(TransporteUnidad $unidad, string $campo): RedirectResponse
    {
        if (! in_array($campo, [...self::CAMPOS_DOCUMENTO, 'fotografia'], true)) {
            abort(404);
        }

        $columna = $campo === 'fotografia' ? 'fotografia_path' : "{$campo}_documento_path";

        if ($unidad->{$columna}) {
            Storage::disk('documentos')->delete($unidad->{$columna});
            $unidad->update([$columna => null]);
        }

        $label = $campo === 'fotografia' ? 'Fotografía' : 'Documento';
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$label} eliminado."]);

        return back();
    }

    /** @return array<string, string|null> */
    private function documentoUrls(TransporteUnidad $unidad): array
    {
        $urls = [];

        foreach (self::CAMPOS_DOCUMENTO as $campo) {
            $columna = "{$campo}_documento_path";
            $urls["{$campo}_documento_url"] = Documentos::url($unidad->{$columna});
        }

        return $urls;
    }

    /** @return array<string, string|null> */
    private function fotografiaUrl(TransporteUnidad $unidad): array
    {
        return ['fotografia_url' => Documentos::url($unidad->fotografia_path)];
    }
}
