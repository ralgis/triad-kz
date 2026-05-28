<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\MissingPageRedirector\RedirectsMissingPages;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Spatie missing-page-redirector — catches every 404 and checks
        // our App\Redirects\DatabaseRedirector (configured in
        // config/missing-page-redirector.php). On a match it rewrites to
        // 301 → new path. Must be in the GLOBAL stack (not just web
        // group) so route-misses (the typical legacy-URL case) flow
        // through it — group middleware only runs after a route matches.
        $middleware->append(RedirectsMissingPages::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
