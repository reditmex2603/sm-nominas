<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
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

        // Generar nonce para que la plantilla Blade lo use en atributos nonce de
        // <script>, <style> y @vite(). CSP determina si las cabeceras lo emiten.
        Vite::useCspNonce();

        $response = $next($request);

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
            $nonce = Vite::cspNonce();
            $policies = [];

            foreach (config('security.csp') as $directive => $value) {
                if (in_array($directive, ['enabled', 'nonce'], true)) {
                    continue;
                }

                $valorFinal = str_replace("'unsafe-inline'", $nonce ? "'nonce-{$nonce}'" : "'unsafe-inline'", $value);

                if ($esArchivo && $directive === 'frame-ancestors') {
                    $policies[] = trim($directive)." 'self'";

                    continue;
                }

                $policies[] = trim($directive).' '.$valorFinal;
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
