<?php

namespace App\Http\Controllers;

use App\Models\ParametroSistema;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function colores(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'color_primario' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'color_sidebar' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ]);

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

    public function subirLogo(Request $request, string $cual): RedirectResponse
    {
        abort_unless(in_array($cual, ['logo', 'isotipo'], true), 404);

        $validated = $request->validate([
            // Sin SVG: los SVG pueden embebir scripts que se ejecutan en el
            // dominio del sitio cuando el usuario navega la imagen directamente.
            'archivo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

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
