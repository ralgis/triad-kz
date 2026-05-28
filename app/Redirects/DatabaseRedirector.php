<?php

declare(strict_types=1);

namespace App\Redirects;

use App\Models\Redirect;
use Spatie\MissingPageRedirector\Redirector\Redirector;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reads 301-map from the `redirects` table (managed via Filament Resource
 * + CSV-bulk-import) rather than from the static config file.
 *
 * Plays nicely with SlugObserver: a path renamed in admin shows up as a
 * new Redirect row instantly, no deploy needed.
 *
 * Side-effect: hits its match counter (`Redirect::recordHit()`) so we
 * can see in admin which old URLs still get traffic — drives the
 * "should we keep this redirect or retire it?" decision in a year.
 */
final class DatabaseRedirector implements Redirector
{
    /**
     * @return array<string, string> ["/from" => "/to"]
     */
    public function getRedirectsFor(Request $request): array
    {
        // Normalize incoming path to the same shape as stored `from`
        // (leading slash, no trailing slash). Otherwise `/old/` (visitor)
        // wouldn't match `/old` (admin or observer-stored).
        //
        // Also URL-decode: the WP cutover map (BuildLegacyRedirects) stores
        // decoded Cyrillic slugs because Yandex/Google index the decoded
        // form for some accounts, while older crawlers still hit the
        // percent-encoded URL. Decoding here lets one stored row match
        // both shapes.
        $path = Redirect::normalizePath(rawurldecode($request->getPathInfo())) ?? '/';

        $row = Redirect::query()->where('from', $path)->first();
        if ($row === null) {
            return [];
        }

        $row->recordHit();

        return [$path => $row->to];
    }
}
