<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Gost;
use Illuminate\Support\Collection;

/**
 * Extracts ГОСТ/Серия references from a product description and
 * resolves them to seeded Gost row IDs.
 *
 * Two callers:
 *   - ImportTriadContent: during a fresh WP→AT migration, walks the
 *     legacy WP description text.
 *   - LinkProductsGosts: thin artisan command that re-runs matching
 *     against the products table that's already in place (post-deploy
 *     of the standards-reference feature). No wp_legacy dependency.
 *
 * Both paths share the same parsing rules so the result is identical.
 */
final class GostMatcher
{
    /**
     * Pull the «ГОСТ/Серия — …» bullet from a product description.
     * Returns the inner text (without the «ГОСТ/Серия —» label) or
     * empty string if no such bullet is present.
     *
     * Tolerates either hyphen-minus or em-dash, optional trailing
     * period, arbitrary whitespace, and nested HTML tags — the
     * legacy WP content sometimes wraps the standard in <span>
     * («<li>ГОСТ/Серия - <span class="st">СТ ТОО 40212232-03-2008</span>.</li>»),
     * so the inner pattern is `.+?` (with /s) and we strip_tags after.
     */
    public static function extractGostLine(string $html): string
    {
        if (! preg_match('#<li>\s*ГОСТ/Серия\s*[-—]\s*(.+?)\s*</li>#us', $html, $m)) {
            return '';
        }

        return trim(strip_tags($m[1]), " \t\n\r\0\x0B.");
    }

    /**
     * Match a free-text «ГОСТ X / Серия Y» line against the seeded
     * reference table, returning the matched Gost row IDs.
     *
     * Strategy: extract every standalone numeric code token
     * ('8020-90', '3.900.1-14', '3.006.1-2.87'), look each up by the
     * `code` column. Legacy variants («3.006.1-2/82(87)»,
     * «3.006.1-2.87 Выпуск N») normalize through a small alias map
     * and «Выпуск N»-suffix stripping.
     *
     * @param Collection<string, Gost> $gostsByCode keyed by `code`
     * @return array<int>
     */
    public static function matchGostIds(string $rawLine, Collection $gostsByCode): array
    {
        if ($rawLine === '') {
            return [];
        }

        // Legacy descriptions used several variants of the same series
        // («3.006.1-2/82(87)», «3.006.1-2.87 Выпуск 2»). Map each to
        // the canonical code in the seeded reference.
        $aliases = [
            '3.006.1-2/82(87)' => '3.006.1-2.87',
            '3.006.1-2/82' => '3.006.1-2.87',
        ];

        $ids = [];

        // Walk tokens that look like numeric standard codes.
        if (! preg_match_all('#(\d+(?:\.\d+)*(?:[/-][\d().]+)*)#u', $rawLine, $matches)) {
            return [];
        }

        foreach ($matches[1] as $token) {
            $code = $aliases[$token] ?? $token;

            // Heuristic: also try stripping trailing «Выпуск N» before
            // matching. The seeded code is the bare series identifier.
            $bare = preg_replace('#\s+Выпуск\s+\d+$#u', '', $code) ?? $code;

            if ($gostsByCode->has($bare)) {
                $ids[$gostsByCode[$bare]->id] = true;
            }
        }

        return array_keys($ids);
    }
}
