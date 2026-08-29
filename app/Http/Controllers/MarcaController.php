<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarColoresMarcaRequest;
use App\Http\Requests\SubirLogoMarcaRequest;
use App\Models\ParametroSistema;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MarcaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('parametros/Marca', [
            'branding' => Branding::datos(),
        ]);
    }

    public function colores(ActualizarColoresMarcaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (isset($validated['color_primario']) && $validated['color_primario'] !== '') {
            ParametroSistema::set(Branding::CLAVE_COLOR_PRIMARIO, $validated['color_primario'], 'Color primario de la marca');
        } else {
            ParametroSistema::clear(Branding::CLAVE_COLOR_PRIMARIO);
        }

        if (isset($validated['color_sidebar']) && $validated['color_sidebar'] !== '') {
            ParametroSistema::set(Branding::CLAVE_COLOR_SIDEBAR, $validated['color_sidebar'], 'Color del panel lateral de la marca');
        } else {
            ParametroSistema::clear(Branding::CLAVE_COLOR_SIDEBAR);
        }

        Inertia::flash('toast', [
            'level' => 'success',
            'message' => 'Colores de la marca actualizados.',
        ]);

        return back();
    }

    public function subirLogo(SubirLogoMarcaRequest $request, string $cual): RedirectResponse
    {
        abort_unless(in_array($cual, ['logo', 'isotipo'], true), 404);

        $validated = $request->validated();

        $clave = $cual === 'logo' ? Branding::CLAVE_LOGO : Branding::CLAVE_ISOTIPO;
        $etiqueta = $cual === 'logo' ? 'logo' : 'isotipo';

        $nuevoPath = $validated['archivo']->store('branding', 'public');

        if ($nuevoPath) {
            $anterior = ParametroSistema::get($clave);
            if (is_string($anterior) && $anterior !== $nuevoPath) {
                Storage::disk('public')->delete($anterior);
            }

            ParametroSistema::set($clave, $nuevoPath, "Archivo {$etiqueta} de la marca");
        }

        Inertia::flash('toast', [
            'level' => 'success',
            'message' => ucfirst($etiqueta).' actualizado correctamente.',
        ]);

        return back();
    }

    public function eliminarLogo(string $cual): RedirectResponse
    {
        abort_unless(in_array($cual, ['logo', 'isotipo'], true), 404);

        $clave = $cual === 'logo' ? Branding::CLAVE_LOGO : Branding::CLAVE_ISOTIPO;
        $etiqueta = $cual === 'logo' ? 'logo' : 'isotipo';

        $path = ParametroSistema::get($clave);
        if (is_string($path)) {
            Storage::disk('public')->delete($path);
        }

        ParametroSistema::clear($clave);

        Inertia::flash('toast', [
            'level' => 'success',
            'message' => ucfirst($etiqueta).' eliminado.',
        ]);

        return back();
    }
}
