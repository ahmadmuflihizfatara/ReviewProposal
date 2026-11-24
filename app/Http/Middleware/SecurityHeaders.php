<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * ASVS 1.2.3 (pertahanan berlapis): tanpa JS inline dan tanpa sumber luar,
     * XSS yang lolos escaping tetap tidak bisa menjalankan skrip.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Saat `npm run dev` (public/hot ada), Vite memakai server & HMR sendiri.
        if (! file_exists(public_path('hot'))) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self'",
                "style-src 'self' 'unsafe-inline'",
                "object-src 'none'",
                "base-uri 'none'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]));
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ASVS 3.4.1: HSTS hanya bermakna lewat HTTPS (browser mengabaikannya di HTTP).
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
