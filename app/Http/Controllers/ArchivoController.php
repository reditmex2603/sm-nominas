<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve archivos sensibles almacenados en el disco privado "documentos".
 *
 * Los documentos (INE, CURP, NSS, licencia, placas, pólizas…) y las evidencias
 * de asistencia NUNCA deben colgar del webroot ni de URLs públicas. Este
 * controlador los entrega únicamente a usuarios autenticados que cuentan con el
 * permiso del módulo correspondiente, validando el path para evitar traversal.
 */
class ArchivoController extends Controller
{
    /** Mapa de tipo visible en la URL → carpeta raíz del disco "documentos". */
    private const TIPOS = [
        'perfil' => 'perfiles/',
        'fotografia' => 'fotografias/',
        'unidad' => 'unidades-transporte/',
        'flotilla' => 'unidades-flotilla/',
        'evidencia' => 'evidencias/',
    ];

    /** Permisos obligatorios por tipo. */
    private const PERMISOS = [
        'perfil' => 'colaboradores',
        'fotografia' => 'colaboradores',
        'unidad' => 'transportes',
        'flotilla' => 'transportes',
        'evidencia' => 'registro-asistencia',
    ];

    public function mostrar(Request $request, string $tipo, string $path): Response|StreamedResponse
    {
        $prefijo = self::TIPOS[$tipo] ?? null;

        abort_if($prefijo === null, 404);

        // Las evidencias las ven los módulos de registro de asistencia y de
        // validación; el resto de tipos se rige por el permiso de su módulo.
        $permisos = $tipo === 'evidencia'
            ? ['registro-asistencia', 'validacion']
            : [(string) self::PERMISOS[$tipo]];

        abort_unless(
            collect($permisos)->contains(fn ($p) => $request->user()->tienePermiso($p)),
            403,
        );

        // Antitravés: evitar escapes de directorio (../) y rutas absolutas.
        $relative = ltrim(rawurldecode($path), '/');

        abort_if($relative === '' || str_contains($relative, '..') || str_starts_with($relative, '..'), 404);

        $storage = Storage::disk('documentos');
        $full = $prefijo.$relative;

        abort_unless($storage->exists($full), 404);

        // Nunca entregar SVG: pueden embebir scripts activos en el dominio.
        abort_if(str_ends_with(strtolower($relative), '.svg'), 403);

        return $storage->response(
            $full,
            null,
            [
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                // El <embed> del PDF/imagen es de mismo origen (página de impresión y archivo
                // comparten host). SAMEORIGIN permite ese embebido sin degradar la protección
                // anti-clickjacking contra orígenes externos (a diferencia de DENY, que bloquea
                // el reader embebido). El middleware SecurityHeaders ya lo deja intacto para
                // respuestas de archivo ($esRespuestaDeArchivo).
                'X-Frame-Options' => 'SAMEORIGIN',
            ],
        );
    }
}
