<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilamentSecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Добавляем заголовки безопасности для Filament админки
        if ($request->is('admin*')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

            // CSP для предотвращения XSS
            $csp = "default-src 'self'; ".
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ".
                   "style-src 'self' 'unsafe-inline'; ".
                   "img-src 'self' data: blob:; ".
                   "font-src 'self'; ".
                   "connect-src 'self'; ".
                   "media-src 'self'; ".
                   "object-src 'none'; ".
                   "child-src 'none'; ".
                   "worker-src 'none'; ".
                   "frame-ancestors 'none'; ".
                   "form-action 'self'; ".
                   "base-uri 'self';";

            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
