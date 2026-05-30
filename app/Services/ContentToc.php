<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Parses H2/H3 headings out of WYSIWYG content, returns a flat list for
 * the TOC sidebar AND emits the same content with auto-assigned id="..."
 * attributes so the anchors actually resolve.
 *
 * Design notes:
 *
 * - We DON'T use a full HTML parser (DOMDocument is heavyweight + mangles
 *   inline content with libxml warnings on partial fragments). Regex on
 *   well-formed TipTap output is enough — TipTap always emits clean
 *   <h2>/<h3> tags without nested heading elements.
 * - IDs are derived from the heading text via Str::slug(...) with a
 *   per-document collision suffix (─2, ─3, ...). Two identical H2s in
 *   the same article would otherwise collide and break in-page anchor
 *   navigation.
 * - If a heading already has an id="…" attribute (admin pasted hand-
 *   written HTML), we keep it — admin override beats auto.
 * - Russian text → Spatie's Str::slug transliterates Cyrillic by default,
 *   producing latin-only slugs ready for URL fragments.
 */
final class ContentToc
{
    /**
     * Extract headings into a flat list for the TOC nav.
     *
     * @return list<array{level: int, text: string, id: string}>
     */
    public function extract(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $seen = [];
        $items = [];

        if (preg_match_all(
            '/<(h[23])(?P<attrs>[^>]*)>(?P<text>.*?)<\/\1>/iu',
            $html,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $m) {
                $level = (int) substr(strtolower($m[1]), 1);
                $text = trim(strip_tags($m['text']));
                if ($text === '') {
                    continue;
                }

                $id = $this->extractExistingId($m['attrs']) ?: $this->makeUniqueId($text, $seen);
                $items[] = ['level' => $level, 'text' => $text, 'id' => $id];
            }
        }

        return $items;
    }

    /**
     * Inject `id="…"` into <h2>/<h3> tags so the TOC anchors land
     * correctly. Idempotent — re-running on already-injected content
     * preserves existing ids without re-suffixing.
     */
    public function injectIds(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $seen = [];

        return (string) preg_replace_callback(
            '/<(h[23])(?P<attrs>[^>]*)>(?P<text>.*?)<\/\1>/iu',
            function (array $m) use (&$seen): string {
                $tag = $m[1];
                $attrs = $m['attrs'];
                $text = $m['text'];

                $existing = $this->extractExistingId($attrs);
                if ($existing !== null) {
                    $seen[$existing] = ($seen[$existing] ?? 0) + 1;

                    return $m[0];
                }

                $plain = trim(strip_tags($text));
                if ($plain === '') {
                    return $m[0];
                }

                $id = $this->makeUniqueId($plain, $seen);
                $newAttrs = rtrim($attrs);

                return "<{$tag}{$newAttrs} id=\"{$id}\">{$text}</{$tag}>";
            },
            $html,
        );
    }

    private function extractExistingId(string $attrs): ?string
    {
        return preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $m) === 1
            ? $m[1]
            : null;
    }

    /**
     * @param array<string, int> $seen Tally of how many times each base slug
     *                                 has been generated in the current pass.
     *                                 Mutated by reference.
     */
    private function makeUniqueId(string $text, array &$seen): string
    {
        $base = Str::slug($text) ?: 'section';
        $seen[$base] = ($seen[$base] ?? 0) + 1;

        return $seen[$base] === 1 ? $base : $base.'-'.$seen[$base];
    }
}
