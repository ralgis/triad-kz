<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Walks every product's description, pulls each «<li>параметр - значение
 * единица;</li>» bullet, and lands the values in the typed columns
 * added by 0180 migration.
 *
 * Idempotent: running it again on a product whose columns are already
 * populated does nothing harmful — it just re-derives the same values
 * from the same description. The original bullet stays in the
 * description text for now; a follow-up cleanup can strip those rows
 * once the admin is happy with the structured display.
 *
 * Weight comes through as «вес - 0.68 тн.» — we convert tn→kg
 * (×1000) because the column is named `weight_kg` and downstream
 * code (Schema.org, price-by-mass calculations, the future filter UI)
 * expects kilograms.
 */
final class ExtractProductSpecs extends Command
{
    protected $signature = 'triad:extract-product-specs
        {--dry-run : Show extracted values without writing them}';

    protected $description = 'Parse legacy <li> spec bullets in products.description and fill the typed spec columns';

    /**
     * Map of legacy Russian parameter labels (lowercased) to the
     * Product column they populate. Multiple labels can target the
     * same column (e.g. «длина» and «длина плиты» both → length_mm)
     * because the legacy WP content used loose phrasing.
     */
    private const LABEL_MAP = [
        'длина' => 'length_mm',
        'длина плиты' => 'length_mm',
        'ширина' => 'width_mm',
        'ширина плиты' => 'width_mm',
        'высота' => 'height_mm',
        'высота плиты' => 'height_mm',
        'толщина' => 'thickness_mm',
        'внутренний диаметр' => 'inner_diameter_mm',
        'внешний диаметр' => 'outer_diameter_mm',
        'наружный диаметр' => 'outer_diameter_mm',
        'диаметр плиты' => 'plate_diameter_mm',
        'диаметр отверстия' => 'hole_diameter_mm',
        'объем бетона' => 'concrete_volume_m3',
        'объём бетона' => 'concrete_volume_m3',
        'марка бетона' => 'concrete_grade',
        'вес' => 'weight_t',
        'расход стали' => 'steel_kg',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $products = Product::query()->orderBy('id')->get();

        $bar = $this->output->createProgressBar($products->count());
        $bar->setFormat('Products: %current%/%max% [%bar%] %message%');

        $filled = 0;
        $skipped = 0;
        $unknownLabels = [];

        foreach ($products as $product) {
            $bar->setMessage($product->name);

            $extracted = $this->extractSpecs((string) $product->description, $unknownLabels);

            if ($extracted === []) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if (! $dryRun) {
                $product->update($extracted);
            }

            $filled++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            '%sИтого: %d заполнено, %d без характеристик.',
            $dryRun ? '[DRY-RUN] ' : '',
            $filled,
            $skipped,
        ));

        if ($unknownLabels !== []) {
            $this->warn('Неопознанные метки в товарах (нужно дополнить LABEL_MAP, если важно):');
            foreach (array_count_values($unknownLabels) as $label => $count) {
                $this->line("  «{$label}» × {$count}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Pull every «<li>label - value unit;</li>» row from the HTML and
     * return [column => typed value] for the rows whose label is
     * registered in LABEL_MAP.
     *
     * Unknown labels are accumulated in $unknownLabels so the CLI can
     * report them — extending LABEL_MAP later is the right cure.
     *
     * @param  array<int, string>  &$unknownLabels  accumulator
     * @return array<string, int|float|string>
     */
    private function extractSpecs(string $html, array &$unknownLabels): array
    {
        if (! preg_match_all('#<li>\s*([^<:-]+?)\s*[-—]\s*([^<]+?)\s*\.?\s*</li>#u', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $out = [];

        foreach ($matches as $m) {
            $label = mb_strtolower(trim($m[1]));
            $rawValue = trim($m[2]);

            if ($label === 'гост/серия' || $label === '') {
                continue;
            }

            if (! isset(self::LABEL_MAP[$label])) {
                $unknownLabels[] = $label;

                continue;
            }

            $column = self::LABEL_MAP[$label];
            $typed = $this->castValue($column, $rawValue);

            if ($typed !== null) {
                $out[$column] = $typed;
            }
        }

        return $out;
    }

    /**
     * Convert a free-form legacy value string to the type expected by
     * the column. Legacy data has consistent quirks:
     *  - decimals come with «.» not «,», but we tolerate both
     *  - mm values come as plain integers with «мм» suffix
     *  - weight comes as «0.68 тн.» — stored as tonnes (no conversion)
     *  - concrete grade comes as «М300» / «М350» — preserve as-is
     */
    private function castValue(string $column, string $value): int|float|string|null
    {
        // Strip the unit suffix and normalize decimal separator.
        // Legacy bullets end with «;» or «.» before the closing </li>;
        // those land in the captured value when the regex's lazy
        // .+? expands greedily past «мм». Trim them off so the
        // numeric cast works.
        $clean = preg_replace('#\s*(мм|м\.куб|м3|м³|кг|тн\.|тн)\s*[.;]?\s*$#ui', '', $value);
        $clean = str_replace(',', '.', (string) $clean);
        $clean = trim((string) $clean, " \t\n\r\0\x0B;.");

        if ($clean === '') {
            return null;
        }

        return match ($column) {
            'concrete_grade' => $clean,

            'length_mm', 'width_mm', 'height_mm', 'thickness_mm',
            'inner_diameter_mm', 'outer_diameter_mm', 'plate_diameter_mm', 'hole_diameter_mm',
                => is_numeric($clean) ? (int) round((float) $clean) : null,

            'concrete_volume_m3' => is_numeric($clean) ? (float) $clean : null,

            'steel_kg' => is_numeric($clean) ? (float) $clean : null,

            // Legacy weight is in tonnes («0.68 тн.») — stored as-is.
            'weight_t' => is_numeric($clean) ? (float) $clean : null,

            default => null,
        };
    }
}
