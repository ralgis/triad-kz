import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

/*
 * Font stack — Drafting Floor design system (see resources/css/app.css
 * for the full rationale).
 *
 * - Russo One: display headlines (H1-H6). Industrial blocky, native
 *   Cyrillic, free Google Fonts. One weight only — Russo One is
 *   single-weight by design.
 * - Onest: body text. Geometric grotesque, native Cyrillic, more
 *   character than Inter. Three weights cover everything.
 * - IBM Plex Mono: numerics, SKUs, ГОСТ marks. The «fishka» — every
 *   number on the site uses this so digits align visually.
 *
 * Bunny Fonts is a privacy-friendly Google Fonts proxy — no Google
 * analytics calls. Same fonts, same hosting performance, no GDPR
 * concerns. The plugin emits @font-face declarations into a small
 * CSS file Vite serves alongside our compiled app.css.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Russo One', {
                    weights: [400],
                }),
                bunny('Onest', {
                    weights: [400, 500, 700],
                }),
                bunny('IBM Plex Mono', {
                    weights: [400, 500, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
