<?php

namespace App\Support;

class Documentos
{
    /** Mapa carpeta del disco "documentos" → tipo de URL de ArchivoController. */
    private const PREFIJOS = [
        'perfiles/' => 'perfil',
        'fotografias/' => 'fotografia',
        'unidades-transporte/' => 'unidad',
        'unidades-flotilla/' => 'flotilla',
        'evidencias/' => 'evidencia',
    ];

    /**
     * Ruta relativa de un documento del disco "documentos". Devuelve null si
     * el path no pertenece a una carpeta conocida (defensa ante desajustes).
     *
     * Se devuelve una URL RELATIVA (no absoluta) a propósito: estos documentos se
     * renderizan siempre en el mismo host desde el que se navega (<img> / <embed> /
     * impresión). Una URL absoluta fijada por APP_URL (p. ej. "localhost:8000")
     * rompe el <embed> cuando el usuario accede por otro host (p. ej. "127.0.0.1:8000"),
     * porque ese host no comparte la cookie de sesión que exige ArchivoController.
     */
    public static function url(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $normalizado = ltrim($path, '/');

        foreach (self::PREFIJOS as $prefijo => $tipo) {
            if (str_starts_with($normalizado, $prefijo)) {
                $relativo = substr($normalizado, strlen($prefijo));

                return '/'.trim(route('archivos.mostrar', ['tipo' => $tipo, 'path' => $relativo], false), '/');
            }
        }

        return null;
    }
}
