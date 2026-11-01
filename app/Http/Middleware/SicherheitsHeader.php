<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Grundlegende Sicherheits-Header für alle Seiten. Die öffentliche Statistik (/stats) setzt ihre
 * eigenen Einbettungs-Header (siehe StatsController) – diese werden hier nicht überschrieben.
 */
class SicherheitsHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $antwort = $next($request);

        $antwort->headers->set('X-Content-Type-Options', 'nosniff');
        $antwort->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Schutz vor Clickjacking (Einbetten der Verwaltung in fremde Seiten)
        if (! $antwort->headers->has('Content-Security-Policy') && ! $antwort->headers->has('X-Frame-Options')) {
            $antwort->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $antwort->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        }

        if ($request->isSecure()) {
            $antwort->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $antwort;
    }
}
