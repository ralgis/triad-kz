<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds `X-Robots-Tag: noindex, nofollow` on any non-production response.
 *
 * Plesk dev.triad.kz uses a multi-layer protection scheme: HTTP basic auth
 * is the primary fence; this header and the matching <meta> in head.blade
 * are belt-and-braces in case the basic auth ever lapses (or a crawler
 * follows a leaked direct URL with credentials).
 *
 * Production is the ONLY environment where indexing is allowed. APP_ENV
 * stays `dev` until cutover (Phase 5), then flips to `production` — at
 * which point this middleware becomes a no-op.
 */
final class EnsureNoindexInNonProd
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! app()->environment('production')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow', false);
        }

        return $response;
    }
}
