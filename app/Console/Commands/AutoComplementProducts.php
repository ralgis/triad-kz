<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Heuristic auto-fill of the «С этим товаром покупают» pivot table
 * (`complementary_products`). For each published+listed product, scores
 * every other product against a set of rules and picks the top-N as
 * its complements. Symmetric: if A → B is written, B → A is too.
 *
 * Scoring:
 * - Same ГОСТ + same category   → +10 (closest analogues)
 * - Same ГОСТ + different cat   → +5  (true complements)
 * - Same category (no ГОСТ tie) → +3  (siblings)
 * - Related-category bonus      → +4  (cross-category, see RELATED_CATEGORIES)
 * - Spec-similarity bonus       → +1 per dim where ratio > 0.7
 *
 * RELATED_CATEGORIES encodes business knowledge: a colodets installation
 * needs rings + bottom plate + top plate + opornoye ring all together,
 * so those four categories should cross-link by default. ФБС блоки
 * ходят парой с armatura mesh. Teplotrassa lotki — with opornye podushki.
 *
 * Run order:
 *   php artisan products:auto-complement --dry-run    # preview
 *   php artisan products:auto-complement              # actually write
 *
 * The write step truncates `complementary_products` and re-inserts —
 * any manual admin overrides made in Filament will be lost. Run with
 * --dry-run first to confirm the heuristic produces sensible pairs;
 * THEN persist; THEN admin can fine-tune individual products in Filament.
 */
final class AutoComplementProducts extends Command
{
    protected $signature = 'products:auto-complement
                            {--dry-run : Print the resulting table without writing to DB}
                            {--limit=6 : Max complements per product}';

    protected $description = 'Heuristic auto-fill of the «С этим товаром покупают» pivot.';

    /**
     * Cross-category «обычно покупают вместе» heuristic. Keys + values
     * are catalog Category slugs. Symmetric pairings listed both ways
     * so the relation is bidirectional.
     *
     * @var array<string, list<string>>
     */
    private const RELATED_CATEGORIES = [
        // Колодезный комплект — всё вместе для одного колодца.
        'beton-koltsa' => ['plity-perekrytiya', 'plity-dnishcha', 'opornye-koltsa'],
        'plity-perekrytiya' => ['beton-koltsa', 'plity-dnishcha', 'opornye-koltsa'],
        'plity-dnishcha' => ['beton-koltsa', 'plity-perekrytiya'],
        'opornye-koltsa' => ['beton-koltsa', 'plity-perekrytiya'],

        // ФБС подвалы — с армирующей сеткой и иногда с плитами перекрытия.
        'fbs' => ['setka-svarnaya', 'plity-perekrytiya'],
        'setka-svarnaya' => ['fbs'],

        // Теплотрасса — лотки + опорные подушки трубопровода.
        'plity-lotkov-teplotrass' => ['opornye-podushki'],
        'opornye-podushki' => ['plity-lotkov-teplotrass'],

        // Арычные лотки — изолированная категория, спутников по
        // другим категориям нет (только siblings внутри своей).
        'arychnye-lotki' => [],
    ];

    private const SPEC_DIMS = [
        'length_mm', 'width_mm', 'height_mm',
        'inner_diameter_mm', 'outer_diameter_mm',
        'plate_diameter_mm',
    ];

    public function handle(): int
    {
        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->published()
            ->listed()
            ->with(['categories', 'gosts'])
            ->get();

        if ($products->isEmpty()) {
            $this->warn('Нет опубликованных товаров (published+listed). Нечего обрабатывать.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $dry = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Просчёт спутников для %d товаров (лимит=%d на товар)...',
            $products->count(),
            $limit,
        ));

        // Forward pairs from each product to its top-N complements.
        // Symmetric inverse is added later so the relation reads cleanly
        // from either side without double-counting.
        $forwardPairs = [];
        $emptyCount = 0;
        foreach ($products as $product) {
            $top = $this->topCandidates($product, $products, $limit);
            if ($top->isEmpty()) {
                $emptyCount++;

                continue;
            }

            foreach ($top as $i => $candidate) {
                $forwardPairs[] = [
                    'product_id' => $product->id,
                    'complementary_product_id' => $candidate->id,
                    'sort_order' => $i,
                ];
            }
        }

        if ($emptyCount > 0) {
            $this->warn(sprintf(
                '%d товаров не получили ни одного спутника (нет категории/ГОСТ/похожих).',
                $emptyCount,
            ));
        }

        $symmetric = $this->makeSymmetric($forwardPairs);

        if ($dry) {
            $this->renderTable($products, $forwardPairs);
            $this->newLine();
            $this->info(sprintf(
                '[dry-run] %d forward + %d inverse = %d total pairs would be written.',
                count($forwardPairs),
                count($symmetric) - count($forwardPairs),
                count($symmetric),
            ));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($symmetric): void {
            DB::table('complementary_products')->delete();
            foreach (array_chunk($symmetric, 200) as $chunk) {
                DB::table('complementary_products')->insert($chunk);
            }
        });

        $this->info(sprintf(
            'Записано %d пар (симметричных) для %d товаров.',
            count($symmetric),
            $products->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * @param Collection<int, Product> $all
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function topCandidates(Product $product, Collection $all, int $limit): \Illuminate\Support\Collection
    {
        /** @var Category|null $primaryCategory */
        $primaryCategory = $product->categories->first();
        $gostIds = $product->gosts->pluck('id')->all();
        $relatedSlugs = $primaryCategory instanceof Category
            ? (self::RELATED_CATEGORIES[$primaryCategory->slug] ?? [])
            : [];

        $candidates = collect();
        foreach ($all as $other) {
            if ($other->id === $product->id) {
                continue;
            }

            $score = $this->scorePair($product, $other, $primaryCategory, $gostIds, $relatedSlugs);
            if ($score > 0) {
                $candidates->push(['product' => $other, 'score' => $score]);
            }
        }

        return $candidates
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('product')
            ->values();
    }

    /**
     * @param list<int> $aGostIds
     * @param list<string> $aRelatedSlugs
     */
    private function scorePair(
        Product $a,
        Product $b,
        ?Category $aCategory,
        array $aGostIds,
        array $aRelatedSlugs,
    ): int {
        $sameCategory = $aCategory instanceof Category
            && $b->categories->contains('id', $aCategory->id);

        $bGostIds = $b->gosts->pluck('id')->all();
        $sameGost = array_intersect($aGostIds, $bGostIds) !== [];

        $relatedCategory = $aRelatedSlugs !== []
            && $b->categories->whereIn('slug', $aRelatedSlugs)->isNotEmpty();

        $score = 0;
        if ($sameGost && $sameCategory) {
            $score += 10;
        } elseif ($sameGost) {
            $score += 5;
        } elseif ($sameCategory) {
            $score += 3;
        }

        if ($relatedCategory && ! $sameCategory) {
            $score += 4;
        }

        // Spec similarity bonus — encourages same-category siblings of
        // similar typo-размер to bubble up over wildly different sizes.
        foreach (self::SPEC_DIMS as $dim) {
            $av = (int) ($a->{$dim} ?? 0);
            $bv = (int) ($b->{$dim} ?? 0);
            if ($av > 0 && $bv > 0) {
                $ratio = min($av, $bv) / max($av, $bv);
                if ($ratio > 0.7) {
                    $score += 1;
                }
            }
        }

        return $score;
    }

    /**
     * Ensures every forward pair (A → B) has an inverse (B → A). Dedup
     * by (product_id, complementary_product_id). Inverse rows get
     * sort_order = 999 so the forward (intentional) ordering wins in
     * the «С этим товаром покупают» block on the catalog page.
     *
     * @param list<array{product_id: int, complementary_product_id: int, sort_order: int}> $forward
     * @return list<array{product_id: int, complementary_product_id: int, sort_order: int}>
     */
    private function makeSymmetric(array $forward): array
    {
        $seen = [];
        $result = [];

        foreach ($forward as $pair) {
            $key = $pair['product_id'].'_'.$pair['complementary_product_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $pair;
        }

        foreach ($forward as $pair) {
            $key = $pair['complementary_product_id'].'_'.$pair['product_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = [
                'product_id' => $pair['complementary_product_id'],
                'complementary_product_id' => $pair['product_id'],
                'sort_order' => 999,
            ];
        }

        return $result;
    }

    /**
     * @param Collection<int, Product> $products
     * @param list<array{product_id: int, complementary_product_id: int, sort_order: int}> $pairs
     */
    private function renderTable(Collection $products, array $pairs): void
    {
        $byProduct = collect($pairs)->groupBy('product_id');
        $rows = [];

        foreach ($products as $product) {
            $names = $byProduct->get($product->id, collect())
                ->sortBy('sort_order')
                ->map(fn (array $p): string => $products->firstWhere('id', $p['complementary_product_id'])?->name ?? '?')
                ->all();

            $rows[] = [
                'товар' => mb_substr($product->name, 0, 35),
                'спутники (по убыванию score)' => mb_substr(implode(' · ', $names) ?: '—', 0, 80),
            ];
        }

        $this->table(['Товар', 'Спутники'], $rows);
    }
}
