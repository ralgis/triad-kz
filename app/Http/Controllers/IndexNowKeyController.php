<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the IndexNow ownership-verification key file. Search engines
 * (Yandex / Bing) fetch /{key}.txt after we POST URLs to the IndexNow
 * endpoint — body must equal the key, plain text.
 *
 * Route-bound to /{key}.txt where {key} is the literal IndexNow key
 * from settings. If the requested key doesn't match the stored one
 * (rotation in flight, or someone probing with a guessed key) we 404 —
 * never echo arbitrary input.
 */
final class IndexNowKeyController extends Controller
{
    public function __invoke(string $key): Response
    {
        $settings = Setting::current();

        abort_unless(
            ! empty($settings->indexnow_key) && hash_equals($settings->indexnow_key, $key),
            404,
        );

        return response($settings->indexnow_key, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
