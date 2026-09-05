<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateColaboradorPerfilRequest;
use App\Models\Colaborador;
use App\Models\ColaboradorDatoBancario;
use App\Models\ColaboradorPerfil;
use App\Support\Documentos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ColaboradorPerfilController extends Controller
{
    /** Documentos de identificación (además del de seguro social) — campo de subida → columna. */
    private const CAMPOS_DOCUMENTO = ['seguro_social', 'ine', 'curp', 'comprobante_domicilio', 'licencia_conducir'];

    public function datosJson(Colaborador $colaborador): JsonResponse
    {
        $perfil = $colaborador->perfil;

        return response()->json([
            'colaborador' => [
                'id' => $colaborador->id,
                'nombre' => $colaborador->nombre,
                'apellidos' => $colaborador->apellidos,
                'tipo' => $colaborador->tipo,
                'categoria' => $colaborador->categoria,
                'nivel' => $colaborador->nivel,
                'sueldo_diario' => $colaborador->sueldo_diario,
                'compensacion_pct' => $colaborador->compensacion_pct,
                'extra_dia_adicional' => $colaborador->extra_dia_adicional,
            ],
            'perfil' => $perfil ? $this->conDatosBancarios($perfil) : null,
        ]);
    }

    public function show(Colaborador $colaborador): Response
    {
        $perfil = $colaborador->perfil;

        return Inertia::render('colaboradores/Perfil', [
            'colaborador' => $colaborador,
            'perfil' => $perfil ? $this->conDatosBancarios($perfil) : null,
        ]);
    }

    public function imprimirPerfil(Colaborador $colaborador): Response
    {
        $perfil = $colaborador->perfil;

        return Inertia::render('colaboradores/ImprimirPerfil', [
            'colaborador' => $colaborador,
            'perfil' => $perfil ? $this->conDatosBancarios($perfil) : null,
        ]);
    }

    public function imprimirDocumentos(Colaborador $colaborador): Response
    {
        $perfil = $colaborador->perfil;

        return Inertia::render('colaboradores/ImprimirDocumentos', [
            'colaborador' => $colaborador,
            'perfil' => $perfil ? $this->conDatosBancarios($perfil) : null,
        ]);
    }

    public function update(UpdateColaboradorPerfilRequest $request, Colaborador $colaborador): RedirectResponse
    {
        $validated = $request->validated();

        $perfil = $colaborador->perfil ?? new ColaboradorPerfil(['colaborador_id' => $colaborador->id]);

        $perfil->fill([
            'alias' => $validated['alias'] ?? null,
            'fecha_ingreso' => $validated['fecha_ingreso'],
            'correo' => $validated['correo'] ?? null,
            'telefono' => $validated['telefono'],
            'whatsapp' => $validated['whatsapp'],
            'redes_sociales' => $validated['redes_sociales'] ?? null,
            'domicilio' => $validated['domicilio'] ?? null,
            'genero' => $validated['genero'] ?? null,
            'ubicacion_maps' => $validated['ubicacion_maps'] ?? null,
            'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
            'tipo_sangre' => $validated['tipo_sangre'] ?? null,
            'alergias' => $validated['alergias'] ?? null,
            'padecimientos_cronicos' => $validated['padecimientos_cronicos'] ?? null,
            'numero_seguro_social' => $validated['numero_seguro_social'] ?? null,
            'contacto_emergencia_1_nombre' => $validated['contacto_emergencia_1_nombre'] ?? null,
            'contacto_emergencia_1_parentesco' => $validated['contacto_emergencia_1_parentesco'] ?? null,
            'contacto_emergencia_1_telefono' => $validated['contacto_emergencia_1_telefono'] ?? null,
            'contacto_emergencia_2_nombre' => $validated['contacto_emergencia_2_nombre'] ?? null,
            'contacto_emergencia_2_parentesco' => $validated['contacto_emergencia_2_parentesco'] ?? null,
            'contacto_emergencia_2_telefono' => $validated['contacto_emergencia_2_telefono'] ?? null,
            'banco' => $validated['banco'] ?? null,
            'beneficiario' => $validated['beneficiario'] ?? null,
            'clave_interbancaria' => $validated['clave_interbancaria'] ?? null,
        ]);

        if ($request->hasFile('fotografia')) {
            if ($perfil->fotografia_path) {
                Storage::disk('documentos')->delete($perfil->fotografia_path);
            }

            if ($path = $request->file('fotografia')->store('fotografias', 'documentos')) {
                $perfil->fotografia_path = $path;
            }
        }

        foreach (self::CAMPOS_DOCUMENTO as $campo) {
            $inputName = "{$campo}_documento";
            $columna = "{$campo}_documento_path";

            if ($request->hasFile($inputName)) {
                if ($perfil->{$columna}) {
                    Storage::disk('documentos')->delete($perfil->{$columna});
                }

                if ($nuevoPath = $request->file($inputName)->store('perfiles', 'documentos')) {
                    $perfil->{$columna} = $nuevoPath;
                }
            }
        }

        $perfil->save();

        $this->sincronizarDatosBancarios($colaborador, $validated['datos_bancarios'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perfil guardado correctamente.']);

        return back();
    }

    public function eliminarDocumento(Colaborador $colaborador, string $campo): RedirectResponse
    {
        if (! in_array($campo, self::CAMPOS_DOCUMENTO, true)) {
            abort(404);
        }

        $columna = "{$campo}_documento_path";
        $perfil = $colaborador->perfil;

        if ($perfil && $perfil->{$columna}) {
            Storage::disk('documentos')->delete($perfil->{$columna});
            $perfil->update([$columna => null]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Documento eliminado.']);

        return back();
    }

    /** @return array<string, string|null> */
    private function documentoUrls(ColaboradorPerfil $perfil): array
    {
        $urls = [];

        foreach (self::CAMPOS_DOCUMENTO as $campo) {
            $columna = "{$campo}_documento_path";
            $urls["{$campo}_documento_url"] = Documentos::url($perfil->{$columna});
        }

        $urls['fotografia_url'] = Documentos::url($perfil->fotografia_path);

        return $urls;
    }

    /**
     * Perfil serializable para Inertia: datos del perfil + URLs de documentos + la lista
     * completa de registros bancarios (1 o más).
     *
     * @return array<string, mixed>
     */
    private function conDatosBancarios(ColaboradorPerfil $perfil): array
    {
        return [
            ...array_merge($perfil->toArray(), $this->documentoUrls($perfil)),
            'datos_bancarios' => $perfil->colaborador->datosBancarios()->orderBy('id')->get(),
        ];
    }

    /**
     * Reemplaza la lista de registros bancarios del colaborador por la recibida (la UI manda
     * siempre la lista completa). Crea/actualiza según traigan id; los registros que ya no
     * estén en la lista se eliminan. El primer registro se espeja en las columnas bancarias
     * de colaborador_perfiles para no romper impresiones históricas (nómina/historial).
     *
     * @param  array<int, array<string, mixed>>  $datos
     */
    private function sincronizarDatosBancarios(Colaborador $colaborador, array $datos): void
    {
        $datos = array_values($datos);

        if ($datos === []) {
            // Sin lista bancaria (cliente que solo envía los campos sueltos de perfil):
            // se respeta lo guardado en las columnas de colaborador_perfiles.
            return;
        }

        $idsEnviados = collect($datos)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        ColaboradorDatoBancario::where('colaborador_id', $colaborador->id)
            ->whereNotIn('id', $idsEnviados)
            ->delete();

        foreach ($datos as $dato) {
            $campos = array_intersect_key($dato, array_flip([
                'banco', 'beneficiario', 'clave_interbancaria', 'numero_tarjeta', 'alias', 'comentario',
            ]));

            if (! empty($dato['id'])) {
                ColaboradorDatoBancario::where('colaborador_id', $colaborador->id)
                    ->where('id', (int) $dato['id'])
                    ->update($campos);
            } else {
                $colaborador->datosBancarios()->create($campos);
            }
        }

        // Se re-consulta el perfil en vez de usar $colaborador->perfil: la relación ya pudo
        // haber quedado cacheada como null por el controlador al crear el perfil.
        $perfil = ColaboradorPerfil::where('colaborador_id', $colaborador->id)->first();

        if ($perfil) {
            $principal = $datos[0];
            $perfil->update([
                'banco' => $principal['banco'] ?? null,
                'beneficiario' => $principal['beneficiario'] ?? null,
                'clave_interbancaria' => $principal['clave_interbancaria'] ?? null,
            ]);
        }
    }
}
