<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cabeceras de Seguridad HTTP
    |--------------------------------------------------------------------------
    |
    | Cabeceras fijas aplicadas por App\Http\Middleware\SecurityHeaders a todas
    | las respuestas HTTP. Se pueden ajustar por entorno vía archivo .env.
    |
    | SECURITY_FORCE_HTTPS: redirige a HTTPS toda petición no segura (producción).
    | SECURITY_HSTS_ENABLED: emite Strict-Transport-Security sobre HTTPS.
    | SECURITY_CSP_ENABLED:  emite Content-Security-Policy (opcional, requiere
    |                        revisar los recursos inline de la aplicación).
    |
    */

    'force_https' => (bool) env('SECURITY_FORCE_HTTPS', false),

    /*
    | Proxies de confianza (p. ej. IPs del balanceador de carga/nginx/CDN) para
    | la correcta detección de HTTPS y dirección IP del cliente. Se omiten
    | cuando el valor está vacío. Ejemplo:
    | TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,203.0.113.1
    */
    'trusted_proxies' => array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', '')),
    )),

    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', false),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => true,
        'preload' => (bool) env('SECURITY_HSTS_PRELOAD', false),
    ],

    'csp' => [
        'enabled' => (bool) env('SECURITY_CSP_ENABLED', false),

        /*
        | La aplicación es Inertia + Vite y usa estilos/scripts inline propios de
        | Vite. Por eso 'unsafe-inline' en script/style. No permitir object-src ni
        | conexiones a terceros salvo las declaradas.
        | Nota para endurecer: mover scripts inline y quitar 'unsafe-inline'
        | (requiere colas de trabajo Vite y re-generación de assets).
        */
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: blob:",
        'font-src' => "'self' data:",
        'connect-src' => "'self' ws: wss:",
        'object-src' => "'none'",
        'frame-ancestors' => "'none'",
        'base-uri' => "'self'",
        'form-action' => "'self'",
    ],

    // Cabeceras fijas (siempre presentes).
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ],

];
