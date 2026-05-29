<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Support\Translit;
use Illuminate\Console\Command;
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
                $this->importCategories();
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

    private function importCategories(): void
    {
        $rows = DB::connection('wp_legacy')
            ->table('terms')
            ->whereIn('term_id', array_keys(self::CATEGORY_MAP))
            ->get(['term_id', 'name']);

        $bar = $this->output->createProgressBar($rows->count());
        $bar->setFormat('Categories: %current%/%max% [%bar%] %message%');

        foreach ($rows as $row) {
            $map = self::CATEGORY_MAP[$row->term_id];
            $bar->setMessage($row->name);

            Category::updateOrCreate(
                ['slug' => $map['slug']],
                [
                    'name' => $row->name,
                    'order' => $map['order'],
                    'published' => true,
                ],
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importProducts(?ZipArchive $zip): void
    {
        $catIds = implode(',', array_keys(self::CATEGORY_MAP));

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

            // Parse ГОСТ out of the excerpt when present. WP excerpts
            // followed a consistent pattern in this dataset
            // ("..., ГОСТ NNNN-YY, Серия ..."); a regex pulls the first
            // ГОСТ/Серия match.
            $gost = null;
            if (preg_match('/(ГОСТ\s+\d+\S*|Серия\s+\S+)/u', (string) $r->post_excerpt, $m)) {
                $gost = $m[1];
            }

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

            // In this legacy dataset, _thumbnail_id is the BLUEPRINT
            // (schematic / чертёж with dimensions) and gallery items are
            // the actual product photos. Confirmed by the client. The
            // mapping below reflects that:
            //   _thumbnail_id           → blueprint  (singleFile)
            //   gallery[0]              → real       (singleFile, primary photo)
            //   gallery[1..n]           → gallery    (multi)
            if ($zip !== null && $r->thumb_id) {
                $this->attachToCollection($product, 'blueprint', (int) $r->thumb_id, $zip);
            }

            if ($zip !== null && $r->gallery_ids) {
                $this->attachGallery($product, (string) $r->gallery_ids, $zip);
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
     * Attach a single WP attachment to a singleFile collection
     * (blueprint or real). Idempotent: skips when the same source
     * file is already attached.
     */
    private function attachToCollection(Product $product, string $collection, int $attachmentId, ZipArchive $zip): void
    {
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

        if ($product->getFirstMedia($collection)?->file_name === basename($entry)) {
            fclose($stream);

            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'triad-img-');
        file_put_contents($tmp, stream_get_contents($stream));
        fclose($stream);

        $product->clearMediaCollection($collection);
        $product->addMedia($tmp)
            ->preservingOriginal(false)
            ->usingFileName(basename($entry))
            ->toMediaCollection($collection);
    }

    /**
     * Split the WC gallery: first item is the primary product photo
     * (singleFile 'real' collection), remainder are additional angle
     * shots (multi 'gallery' collection). Mirrors the catalog UI which
     * shows one main «Фото изделия» tab and a separate gallery strip.
     */
    private function attachGallery(Product $product, string $idsCsv, ZipArchive $zip): void
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', $idsCsv))));
        if (! $ids) {
            return;
        }

        $paths = DB::connection('wp_legacy')
            ->table('postmeta')
            ->whereIn('post_id', $ids)
            ->where('meta_key', '_wp_attached_file')
            ->pluck('meta_value', 'post_id');

        // Wipe both target collections and rebuild — both involve >1
        // WP attachment_id and idempotency by file_name gets messy
        // with reorder.
        $product->clearMediaCollection('real');
        $product->clearMediaCollection('gallery');

        foreach ($ids as $i => $attId) {
            $relPath = $paths[$attId] ?? null;
            if (! $relPath) {
                continue;
            }

            $entry = 'files/wp-content/uploads/'.$relPath;
            $stream = $zip->getStream($entry);
            if ($stream === false) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'triad-gal-');
            file_put_contents($tmp, stream_get_contents($stream));
            fclose($stream);

            $product->addMedia($tmp)
                ->preservingOriginal(false)
                ->usingFileName(basename($entry))
                ->toMediaCollection($i === 0 ? 'real' : 'gallery');
        }
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
