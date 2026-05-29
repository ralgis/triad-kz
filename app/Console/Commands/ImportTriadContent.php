<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Gost;
use App\Models\Page;
use App\Models\Product;
use App\Support\Translit;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * One-shot migrator from the legacy WP4.3/WooCommerce dump to AT models.
 *
 * Idempotent: re-running updates existing rows by slug rather than
 * inserting duplicates. Safe to run repeatedly during dev iteration
 * and again on prod after the local dry-run.
 *
 * Usage:
 *   php artisan triad:import-content
 *   php artisan triad:import-content --zip=/path/to/triad.kz.zip
 *   php artisan triad:import-content --only=categories
 *
 * Requires the `wp_legacy` connection (config/database.php) to point at
 * a MariaDB/MySQL holding the dump — locally that's the docker compose
 * helper:  docker run -d --name triad-wp -e MARIADB_ROOT_PASSWORD=temp
 *          -e MARIADB_DATABASE=wp_legacy -p 33063:3306 mariadb:11
 */
final class ImportTriadContent extends Command
{
    protected $signature = 'triad:import-content
        {--zip= : Path to triad.kz__*.zip with wp-content/uploads}
        {--only=* : Subset: categories|products|pages|articles}';

    protected $description = 'Phase 4: import categories + products + static pages from the legacy WP dump';

    /**
     * Map of WP term_id → canonical AT slug + display order. Picked at
     * agreement with the client (Phase 0); slugs use the Translit
     * helper but are pinned here so future tweaks to the translit table
     * can't accidentally move category URLs.
     *
     * @var array<int, array{slug: string, order: int}>
     */
    private const CATEGORY_MAP = [
        65 => ['slug' => 'beton-koltsa',          'order' => 10],
        67 => ['slug' => 'plity-perekrytiya',     'order' => 20],
        68 => ['slug' => 'plity-dnishcha',        'order' => 30],
        69 => ['slug' => 'opornye-koltsa',        'order' => 40],
        70 => ['slug' => 'fbs',                   'order' => 50],
        72 => ['slug' => 'plity-lotkov-teplotrass', 'order' => 60],
        73 => ['slug' => 'opornye-podushki',      'order' => 70],
        74 => ['slug' => 'arychnye-lotki',        'order' => 80],
        75 => ['slug' => 'setka-svarnaya',        'order' => 90],
    ];

    /**
     * Static pages we surface on the new site. Keyed by WP post ID,
     * value is the canonical AT slug + the route it'll live at.
     *
     * @var array<int, string>
     */
    private const PAGE_MAP = [
        2036 => 'about',
        1810 => 'contacts',
        3204 => 'gosts',
    ];

    public function handle(): int
    {
        try {
            DB::connection('wp_legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->error('wp_legacy connection failed: '.$e->getMessage());
            $this->line('Start the MariaDB helper: docker run -d --name triad-wp \\');
            $this->line('  -e MARIADB_ROOT_PASSWORD=temp -e MARIADB_DATABASE=wp_legacy \\');
            $this->line('  -p 33063:3306 mariadb:11');
            $this->line('Then load the dump:');
            $this->line('  docker exec -i triad-wp mariadb -uroot -ptemp wp_legacy < dump.sql');

            return self::FAILURE;
        }

        $only = $this->option('only') ?: ['categories', 'products', 'pages'];
        $zip = $this->openZip($this->option('zip'));

        DB::transaction(function () use ($only, $zip) {
            if (in_array('categories', $only, true)) {
                $this->importCategories($zip);
            }
            if (in_array('products', $only, true)) {
                $this->importProducts($zip);
            }
            if (in_array('pages', $only, true)) {
                $this->importPages();
            }
        });

        $zip?->close();
        $this->info('Done.');

        return self::SUCCESS;
    }

    private function importCategories(?ZipArchive $zip = null): void
    {
        // WC stores category covers in woocommerce_termmeta under
        // `thumbnail_id` (separate from generic termmeta) — pull both
        // in one shot so the cover hop is a single read per term.
        $rows = DB::connection('wp_legacy')->select('
            SELECT t.term_id, t.name, tm.meta_value AS thumb_id
            FROM G5cX6018k4_terms t
            LEFT JOIN G5cX6018k4_woocommerce_termmeta tm
              ON tm.woocommerce_term_id=t.term_id AND tm.meta_key="thumbnail_id"
            WHERE t.term_id IN ('.implode(',', array_keys(self::CATEGORY_MAP)).')
        ');

        $bar = $this->output->createProgressBar(count($rows));
        $bar->setFormat('Categories: %current%/%max% [%bar%] %message%');

        foreach ($rows as $row) {
            $map = self::CATEGORY_MAP[$row->term_id];
            $bar->setMessage($row->name);

            $category = Category::updateOrCreate(
                ['slug' => $map['slug']],
                [
                    'name' => $row->name,
                    'order' => $map['order'],
                    'published' => true,
                ],
            );

            if ($zip !== null && $row->thumb_id) {
                $this->attachAttachmentToModel($category, 'cover', (int) $row->thumb_id, $zip);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importProducts(?ZipArchive $zip): void
    {
        $catIds = implode(',', array_keys(self::CATEGORY_MAP));

        // Reference table for ГОСТ/Серия linking. Pulled once because
        // we'll match every product's free-text «ГОСТ X / Серия Y» line
        // against this set. GostsSeeder must run before this command —
        // we let the lookup miss gracefully (warn, skip the link) so
        // the import doesn't blow up if it didn't.
        $gostsByCode = Gost::query()->whereNotNull('code')->get()->keyBy('code');

        // Only Cyrillic-titled products in our category set: the WP dump
        // includes one leftover GoodStore demo ("Keni Jeans Mog") tagged
        // to a ЖБИ category that we explicitly exclude.
        $rows = DB::connection('wp_legacy')->select("
            SELECT p.ID, p.post_title, p.post_name, p.post_content, p.post_excerpt,
                   sku.meta_value      AS sku,
                   price.meta_value    AS price,
                   thumb.meta_value    AS thumb_id,
                   gallery.meta_value  AS gallery_ids
            FROM G5cX6018k4_posts p
            LEFT JOIN G5cX6018k4_postmeta sku     ON sku.post_id=p.ID     AND sku.meta_key='_sku'
            LEFT JOIN G5cX6018k4_postmeta price   ON price.post_id=p.ID   AND price.meta_key='_regular_price'
            LEFT JOIN G5cX6018k4_postmeta thumb   ON thumb.post_id=p.ID   AND thumb.meta_key='_thumbnail_id'
            LEFT JOIN G5cX6018k4_postmeta gallery ON gallery.post_id=p.ID AND gallery.meta_key='_product_image_gallery'
            WHERE p.post_type='product'
              AND p.post_status='publish'
              AND p.post_title NOT REGEXP '^[A-Za-z]'
              AND p.ID IN (
                  SELECT object_id FROM G5cX6018k4_term_relationships
                  WHERE term_taxonomy_id IN ($catIds)
              )
            ORDER BY p.ID
        ");

        $cats = Category::query()
            ->whereIn('slug', array_column(self::CATEGORY_MAP, 'slug'))
            ->get()
            ->keyBy('slug');

        $bar = $this->output->createProgressBar(count($rows));
        $bar->setFormat('Products:   %current%/%max% [%bar%] %message%');

        foreach ($rows as $r) {
            $slug = Translit::slug((string) $r->post_title);
            $bar->setMessage($r->post_title);

            // Parse ГОСТ/Серия — these live inside post_content as a
            // bullet «<li>ГОСТ/Серия — ГОСТ X / Серия Y</li>». We
            // extract every standalone «ГОСТ NNNN-NN» / «Серия N.N.N-NN»
            // token, then match each to the reference table by numeric
            // code. Unmatched tokens are logged so the seeder can be
            // expanded later.
            $rawGostLine = $this->extractGostLine((string) $r->post_content);
            $gostIds = $this->matchGostsForProduct($rawGostLine, $gostsByCode);

            // Legacy free-text ГОСТ column — kept until we drop it in a
            // follow-up migration. Carries the raw line for traceability
            // even after the relation goes through.
            $gost = $rawGostLine ?: null;

            // Real prices set in WP — but per Phase 0 agreement nothing
            // was actually selling there, so we ship with prices hidden
            // by default so admin double-checks each before public
            // display. Price value is preserved so the admin only flips
            // a flag.
            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $r->post_title,
                    'sku' => $r->sku ?: null,
                    'gost' => $gost,
                    'description' => $this->cleanContent((string) $r->post_content),
                    'price' => $r->price !== null && $r->price !== '' ? (string) $r->price : null,
                    'price_unit' => 'за шт',
                    'price_visible' => false,
                    'unit_for_order' => 'шт',
                    'published' => true,
                    'featured' => false,
                    'in_stock' => true,
                ],
            );

            // Pivot M2M. WP allows a product in multiple cats; mirror that.
            $wpCatIds = DB::connection('wp_legacy')
                ->table('term_relationships')
                ->where('object_id', $r->ID)
                ->whereIn('term_taxonomy_id', array_keys(self::CATEGORY_MAP))
                ->pluck('term_taxonomy_id')
                ->toArray();

            $atCatIds = array_filter(array_map(
                fn (int $wpId) => $cats[self::CATEGORY_MAP[$wpId]['slug']]?->id,
                $wpCatIds,
            ));

            if ($atCatIds) {
                $product->categories()->sync($atCatIds);
            }

            $product->gosts()->sync($gostIds);

            // All product images go into a single 'images' collection
            // (drag-orderable in Filament). Display order during seed:
            //   blueprint (_thumbnail_id) → real (gallery[0]) → gallery[1..]
            // Admin can drag-reorder after the fact to put the best
            // photo first. Clears+rebuilds for idempotent re-runs.
            if ($zip !== null) {
                $product->clearMediaCollection('images');

                $sources = [];
                if ($r->thumb_id) {
                    $sources[] = (int) $r->thumb_id;
                }
                if ($r->gallery_ids) {
                    foreach (explode(',', (string) $r->gallery_ids) as $gid) {
                        $sources[] = (int) $gid;
                    }
                }

                $this->attachAttachmentsAsImages($product, array_values(array_filter($sources)), $zip);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importPages(): void
    {
        $rows = DB::connection('wp_legacy')
            ->table('posts')
            ->whereIn('ID', array_keys(self::PAGE_MAP))
            ->get(['ID', 'post_title', 'post_content']);

        $bar = $this->output->createProgressBar($rows->count());
        $bar->setFormat('Pages:      %current%/%max% [%bar%] %message%');

        foreach ($rows as $row) {
            $slug = self::PAGE_MAP[$row->ID];
            $bar->setMessage($row->post_title);

            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $row->post_title,
                    'content' => $this->cleanContent((string) $row->post_content),
                ],
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function openZip(?string $path): ?ZipArchive
    {
        if ($path === null) {
            $this->warn('No --zip supplied; product images will be skipped.');

            return null;
        }
        if (! File::exists($path)) {
            $this->error("Zip not found: {$path}");

            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->error("Failed to open zip: {$path}");

            return null;
        }

        return $zip;
    }

    /**
     * Attach a single WP attachment to a singleFile collection on any
     * Spatie-HasMedia model (Product blueprint, Category cover, …).
     * Idempotent: skips when the same source file is already attached.
     */
    private function attachAttachmentToModel(object $model, string $collection, int $attachmentId, ZipArchive $zip): void
    {
        assert(method_exists($model, 'getFirstMedia'));

        $path = DB::connection('wp_legacy')
            ->table('postmeta')
            ->where('post_id', $attachmentId)
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        if (! $path) {
            return;
        }

        $entry = 'files/wp-content/uploads/'.$path;
        $stream = $zip->getStream($entry);
        if ($stream === false) {
            return;
        }

        if ($model->getFirstMedia($collection)?->file_name === basename($entry)) {
            fclose($stream);

            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'triad-img-');
        file_put_contents($tmp, stream_get_contents($stream));
        fclose($stream);

        $model->clearMediaCollection($collection);
        $model->addMedia($tmp)
            ->preservingOriginal(false)
            ->usingFileName(basename($entry))
            ->toMediaCollection($collection);
    }

    /**
     * Bulk-add WP attachment IDs into Product's unified 'images'
     * collection, preserving the input order so the first ID becomes
     * the primary (catalog card) image.
     *
     * @param array<int> $attachmentIds
     */
    private function attachAttachmentsAsImages(Product $product, array $attachmentIds, ZipArchive $zip): void
    {
        if (! $attachmentIds) {
            return;
        }

        $paths = DB::connection('wp_legacy')
            ->table('postmeta')
            ->whereIn('post_id', $attachmentIds)
            ->where('meta_key', '_wp_attached_file')
            ->pluck('meta_value', 'post_id');

        foreach ($attachmentIds as $attId) {
            $relPath = $paths[$attId] ?? null;
            if (! $relPath) {
                continue;
            }

            $entry = 'files/wp-content/uploads/'.$relPath;
            $stream = $zip->getStream($entry);
            if ($stream === false) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'triad-img-');
            file_put_contents($tmp, stream_get_contents($stream));
            fclose($stream);

            $product->addMedia($tmp)
                ->preservingOriginal(false)
                ->usingFileName(basename($entry))
                ->toMediaCollection('images');
        }
    }

    /**
     * Pull the «ГОСТ/Серия — …» bullet from a product description.
     * Returns the inner text (without the «ГОСТ/Серия —» label) or
     * empty string if no such bullet is present.
     */
    private function extractGostLine(string $html): string
    {
        if (! preg_match('#<li>\s*ГОСТ/Серия\s*[-—]\s*([^<]+?)\s*\.?\s*</li>#u', $html, $m)) {
            return '';
        }

        return trim($m[1]);
    }

    /**
     * Match a free-text «ГОСТ X / Серия Y» line against the seeded
     * reference table, returning the matched Gost row IDs.
     *
     * Strategy: extract every standalone numeric code token
     * ('8020-90', '3.900.1-14', '3.006.1-2.87'), look each up by the
     * `code` column. Matches that fall through (e.g. legacy text
     * «Серия 3.006.1-2/82(87)» normalizes to '3.006.1-2.87') are
     * passed through a small alias map below.
     *
     * @param  Collection<string, Gost>  $gostsByCode  keyed by `code`
     * @return array<int>
     */
    private function matchGostsForProduct(string $rawLine, Collection $gostsByCode): array
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

        // Walk tokens that look like numeric standard codes:
        // - 4-5 digits + dash + 2 digits  ('8020-90', '13579-78')
        // - dotted code ending in /-NN     ('3.900.1-14', '3.006.1-2.87')
        // - dotted code with /letters       ('3.006.1-2/82(87)')
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

        if (empty($ids)) {
            $this->warn("  ! unmatched ГОСТ/Серия line: {$rawLine}");
        }

        return array_keys($ids);
    }

    /**
     * Strip WP shortcodes + dangerous JS/iframe. WP content often has
     * Visual Composer / shortcode soup that means nothing in our new
     * stack — emptying the bracket tags leaves the human-written
     * paragraphs intact.
     */
    private function cleanContent(string $html): string
    {
        // WP-shortcodes: [vc_row], [/vc_row], [jaw_icon ...], etc.
        $html = (string) preg_replace('/\[\/?[a-z_]+[^\]]*\]/i', '', $html);
        // Strip <script> + <iframe> outright.
        $html = (string) preg_replace('#<(script|iframe)[^>]*>.*?</\1>#is', '', $html);

        return trim($html);
    }
}
