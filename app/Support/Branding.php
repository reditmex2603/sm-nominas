<?php

namespace App\Support;

use App\Models\ParametroSistema;
use Illuminate\Support\Facades\Storage;

class Branding
{
    public const CLAVE_COLOR_PRIMARIO = 'branding_color_primario';

    public const CLAVE_COLOR_SIDEBAR = 'branding_color_sidebar';

    public const CLAVE_LOGO = 'branding_logo_path';

    public const CLAVE_ISOTIPO = 'branding_isotipo_path';

    public static function colorPrimario(): ?string
    {
        return static::normalizarHex(ParametroSistema::get(static::CLAVE_COLOR_PRIMARIO));
    }

    public static function colorSidebar(): ?string
    {
        return static::normalizarHex(ParametroSistema::get(static::CLAVE_COLOR_SIDEBAR));
    }

    public static function logoUrl(): ?string
    {
        return static::urlDePath(ParametroSistema::get(static::CLAVE_LOGO));
    }

    public static function isotipoUrl(): ?string
    {
        return static::urlDePath(ParametroSistema::get(static::CLAVE_ISOTIPO));
    }

    public static function datos(): array
    {
        return [
            'nombre' => config('app.name'),
            'color_primario' => static::colorPrimario(),
            'color_sidebar' => static::colorSidebar(),
            'logo_url' => static::logoUrl(),
            'isotipo_url' => static::isotipoUrl(),
        ];
    }

    private static function urlDePath(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private static function normalizarHex(mixed $valor): ?string
    {
        if (! is_string($valor) || ! preg_match('/^#?[0-9A-Fa-f]{6}$/', trim($valor))) {
            return null;
        }

        $hex = ltrim(trim($valor), '#');

        return '#'.strtoupper($hex);
    }
}
