<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Aplica cabeceras de seguridad y, cuando está habilitado por entorno,
     * redirección a HTTPS, HSTS y Content-Security-Policy.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('security.force_https') && ! $request->isSecure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }

        $response = $next($request);

        // Los archivos binarios embebidos en <embed>/<img> (PDF, imágenes) no son páginas
        // HTML vulnerables a clickjacking. Aplicarles X-Frame-Options/DENY o frame-ancestors
        // hace que el navegador NO renderice estos archivos dentro del documento, por lo que
        // se omiten esas cabeceras para respuestas de archivos, conservándolas en el HTML.
        $esArchivo = $this->esRespuestaDeArchivo($response);

        foreach (config('security.headers', []) as $header => $value) {
            if ($esArchivo && in_array($header, ['X-Frame-Options'], true)) {
                continue;
            }

            $response->headers->set($header, $value);
        }

        if (config('security.hsts.enabled') && $request->isSecure()) {
            $hsts = 'max-age='.config('security.hsts.max_age');
            $hsts .= config('security.hsts.include_subdomains') ? '; includeSubDomains' : '';
            $hsts .= config('security.hsts.preload') ? '; preload' : '';

            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        if (config('security.csp.enabled')) {
            $policies = [];

            foreach (config('security.csp') as $directive => $value) {
                if ($directive === 'enabled') {
                    continue;
                }

                if ($esArchivo && $directive === 'frame-ancestors') {
                    // El <embed>/<img> del archivo es de mismo origen; se permite el framing
                    // propio sin aflojar la restricción para orígenes externos.
                    $policies[] = trim($directive)." 'self'";
                    continue;
                }

                $policies[] = trim($directive).' '.$value;
            }

            $response->headers->set('Content-Security-Policy', implode('; ', $policies));
        }

        return $response;
    }

    /**
     * Determina si la respuesta entrega un archivo embebible (PDF o imagen),
     * en cuyo caso no aplican las cabeceras de framing (X-Frame-Options / CSP
     * frame-ancestors). Detectado por el Content-Type, no por el tipo de clase.
     */
    private function esRespuestaDeArchivo(Response $response): bool
    {
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        return str_starts_with($contentType, 'application/pdf')
            || str_starts_with($contentType, 'image/');
    }
}
