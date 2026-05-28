<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Support\Translit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Generates the Phase 5 cutover 301-map from the WP dump + AT seeded
 * content. Each old URL the WP installation served (category archives,
 * product detail pages, static pages, homepage variant) gets a row in
 * the `redirects` table pointing at the equivalent AT URL.
 *
 * Why generate, not crawl: when cutover happens triad.kz is still on
 * the old install and basic auth gates dev — a live crawler can't see
 * either reliably. The WP dump has every published post_name we need,
 * so we compute the URL shape WP would have served and pair it with
 * the AT equivalent we just imported.
 *
 * Idempotent: re-running upserts by `from`, mirroring SlugObserver.
 *
 * Requires the `wp_legacy` connection — see triad:import-content's
 * docstring for the docker helper.
 */
final class BuildLegacyRedirects extends Command
{
    protected $signature = 'triad:build-redirects {--dry-run : Print proposed rows without writing}';

    protected $description = 'Phase 5: build the 301-map from the WP dump → AT URLs';

    /**
     * WP product_cat term_id → AT Category slug. Duplicate of
     * ImportTriadContent::CATEGORY_MAP — we keep the redirect builder
     * standalone so it can run on a prod box that's already been
     * seeded, without the seeder constants needing to be public.
     *
     * @var array<int, string>
     */
    private const CATEGORY_BY_WP_ID = [
        65 => 'beton-koltsa',
        67 => 'plity-perekrytiya',
        68 => 'plity-dnishcha',
        69 => 'opornye-koltsa',
        70 => 'fbs',
        72 => 'plity-lotkov-teplotrass',
        73 => 'opornye-podushki',
        74 => 'arychnye-lotki',
        75 => 'setka-svarnaya',
    ];

    /**
     * WP page ID → AT page slug. Same map as the importer.
     *
     * @var array<int, string>
     */
    private const PAGE_BY_WP_ID = [
        2036 => 'about',
        1810 => 'contacts',
        3204 => 'gosts',
    ];

    /**
     * Hand-coded one-offs: legacy URLs that don't fit the generic
     * category/product/page rules. Picked off the live triad.kz
     * footer + GSC top-pages export.
     *
     * @var array<string, string>
     */
    private const STATIC_MAP = [
        // WP homepage variant exposed during a redesign
        '/homepage-1-variation-2' => '/',
        // WooCommerce default shop URL that ranked but never converted
        '/shop' => '/catalog',
        '/catalog-продукции' => '/catalog',
    ];

    public function handle(): int
    {
        try {
            DB::connection('wp_legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->error('wp_legacy connection failed: '.$e->getMessage());
            $this->line('See triad:import-content for the docker helper setup.');

            return self::FAILURE;
        }

        $rows = collect()
            ->merge($this->buildCategoryRedirects())
            ->merge($this->buildProductRedirects())
            ->merge($this->buildPageRedirects())
            ->merge($this->buildStaticRedirects())
            ->unique('from')
            ->values();

        $this->info('Proposed redirects: '.$rows->count());

        if ($this->option('dry-run')) {
            $this->table(['from', 'to'], $rows->map(fn ($r) => [$r['from'], $r['to']])->toArray());

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($rows->count());
        foreach ($rows as $row) {
            Redirect::updateOrCreate(
                ['from' => $row['from']],
                ['to' => $row['to'], 'status' => 301],
            );
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Done. Total Redirect rows: '.Redirect::count());

        return self::SUCCESS;
    }

    /**
     * @return array<array{from: string, to: string}>
     */
    private function buildCategoryRedirects(): array
    {
        $rows = DB::connection('wp_legacy')
            ->table('terms')
            ->whereIn('term_id', array_keys(self::CATEGORY_BY_WP_ID))
            ->get(['term_id', 'slug']);

        $out = [];
        foreach ($rows as $row) {
            $atSlug = self::CATEGORY_BY_WP_ID[$row->term_id];
            $oldSlug = urldecode((string) $row->slug);
            $out[] = ['from' => '/product-category/'.$oldSlug, 'to' => '/catalog/'.$atSlug];
        }

        return $out;
    }

    /**
     * @return array<array{from: string, to: string}>
     */
    private function buildProductRedirects(): array
    {
        $catIds = implode(',', array_keys(self::CATEGORY_BY_WP_ID));

        $rows = DB::connection('wp_legacy')->select("
            SELECT p.post_title, p.post_name, tr.term_taxonomy_id AS wp_cat_id
            FROM G5cX6018k4_posts p
            JOIN G5cX6018k4_term_relationships tr ON tr.object_id=p.ID
            WHERE p.post_type='product'
              AND p.post_status='publish'
              AND p.post_title NOT REGEXP '^[A-Za-z]'
              AND tr.term_taxonomy_id IN ($catIds)
        ");

        // One product → multiple categories on the WP side. Pick the
        // first (lowest term_id) as the canonical so the URL the new
        // site serves matches the URL Product::url() computes from the
        // M2M pivot's first row.
        $seen = [];
        $out = [];
        foreach ($rows as $r) {
            if (isset($seen[$r->post_title])) {
                continue;
            }
            $seen[$r->post_title] = true;

            $atProductSlug = Translit::slug((string) $r->post_title);
            $atCatSlug = self::CATEGORY_BY_WP_ID[(int) $r->wp_cat_id] ?? null;
            if ($atCatSlug === null) {
                continue;
            }

            $oldSlug = urldecode((string) $r->post_name);
            $out[] = [
                'from' => '/product/'.$oldSlug,
                'to' => '/catalog/'.$atCatSlug.'/'.$atProductSlug,
            ];
        }

        return $out;
    }

    /**
     * @return array<array{from: string, to: string}>
     */
    private function buildPageRedirects(): array
    {
        $rows = DB::connection('wp_legacy')
            ->table('posts')
            ->whereIn('ID', array_keys(self::PAGE_BY_WP_ID))
            ->get(['ID', 'post_name']);

        $out = [];
        foreach ($rows as $row) {
            $atSlug = self::PAGE_BY_WP_ID[$row->ID];
            $oldSlug = urldecode((string) $row->post_name);
            $out[] = ['from' => '/'.$oldSlug, 'to' => '/'.$atSlug];
        }

        return $out;
    }

    /**
     * @return array<array{from: string, to: string}>
     */
    private function buildStaticRedirects(): array
    {
        $out = [];
        foreach (self::STATIC_MAP as $from => $to) {
            $out[] = ['from' => $from, 'to' => $to];
        }

        return $out;
    }
}
