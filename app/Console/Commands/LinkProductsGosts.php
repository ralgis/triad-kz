<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Gost;
use App\Models\Product;
use App\Support\GostMatcher;
use Illuminate\Console\Command;

/**
 * Backfill the product↔gost M2M pivot from existing product descriptions.
 *
 * Use case: «ГОСТы и Серии» reference shipped after products were
 * already imported (so products.description still carries the original
 * «<li>ГОСТ/Серия — X / Y</li>» bullet, but the pivot is empty).
 * Running this once links every product to its matching reference rows
 * without re-running the full WP→AT migration.
 *
 * Idempotent — sync() replaces the pivot rows each run, so re-runs are
 * safe and pick up newly-added reference rows automatically.
 *
 * Usage:
 *   php artisan triad:link-gosts
 *   php artisan triad:link-gosts --dry-run
 */
final class LinkProductsGosts extends Command
{
    protected $signature = 'triad:link-gosts
        {--dry-run : Show what would change without writing to the pivot}';

    protected $description = 'Link products to ГОСТ/Серия reference rows by parsing existing descriptions';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $gostsByCode = Gost::query()
            ->whereNotNull('code')
            ->get()
            ->keyBy('code');

        if ($gostsByCode->isEmpty()) {
            $this->error('Справочник пуст. Сначала запусти: php artisan db:seed --class=GostsSeeder');

            return self::FAILURE;
        }

        $this->info(sprintf('Загружено %d записей справочника.', $gostsByCode->count()));

        $products = Product::query()->orderBy('id')->get();

        $bar = $this->output->createProgressBar($products->count());
        $bar->setFormat('Products: %current%/%max% [%bar%] %message%');

        $linked = 0;
        $unmatched = 0;
        $skipped = 0;
        $unmatchedRows = [];

        foreach ($products as $product) {
            $bar->setMessage($product->name);

            $rawLine = GostMatcher::extractGostLine((string) $product->description);

            if ($rawLine === '') {
                $skipped++;
                $bar->advance();

                continue;
            }

            $ids = GostMatcher::matchGostIds($rawLine, $gostsByCode);

            if (empty($ids)) {
                $unmatched++;
                $unmatchedRows[] = sprintf('  #%d %s — %s', $product->id, $product->name, $rawLine);
                $bar->advance();

                continue;
            }

            if (! $dryRun) {
                $product->gosts()->sync($ids);
            }

            $linked++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            '%sИтого: %d залинковано, %d без матча, %d без строки ГОСТ/Серия.',
            $dryRun ? '[DRY-RUN] ' : '',
            $linked,
            $unmatched,
            $skipped,
        ));

        if ($unmatchedRows !== []) {
            $this->warn('Строки без матча (добавь записи в справочник через /admin/gosts и перезапусти):');
            foreach ($unmatchedRows as $row) {
                $this->line($row);
            }
        }

        return self::SUCCESS;
    }
}
