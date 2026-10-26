<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de 05 §5. Solo en producción: el servidor de Vite en desarrollo
 * sirve scripts desde otro origen y la CSP los bloquearía.
 */
class SecurityHeaders
{
    private const HEADERS = [
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'",
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Strict-Transport-Security' => 'max-age=31536000',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->isProduction()) {
            $response->headers->add(self::HEADERS);
        }

        return $response;
    }
}
