<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Response;

/**
 * Env-aware robots.txt. Lives at the framework layer (not
 * public/robots.txt) so we can flip behavior by env-var alone, with no
 * deploy needed for the Phase 5 cutover.
 *
 * - dev/local/testing  → block everything; the Plesk basic-auth fence
 *   should already keep crawlers out, but a hand-written Disallow makes
 *   the intent explicit and survives anyone disabling basic auth by
 *   mistake.
 * - production         → standard allow with a Sitemap pointer
 *   (Sitemap controller lands in Phase 3 — until then we just point at
 *   the canonical URL).
 */
final class RobotsController extends Controller
{
    public function __invoke(Application $app): Response
    {
        $body = $app->environment('production')
            ? "User-agent: *\nDisallow:\nSitemap: ".url('/sitemap.xml')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
