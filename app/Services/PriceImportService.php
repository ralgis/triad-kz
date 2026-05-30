<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PriceImport;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

/**
 * Excel price-import pipeline. Two stages:
 *
 *   parse() + buildPreview()   →   show admin what would change
 *   apply()                    →   commit + log to PriceImport
 *
 * MVP assumptions about the file format (decision 2026-05-31, iterate
 * later when the client's actual prices file lands):
 *
 *   Column A = SKU       (e.g. КС10.9, ФБС24.4.6-Т)
 *   Column B = Price     (12500, «12 500», «12,500.00 ₸» — we strip
 *                         everything that isn't a digit or comma/dot)
 *
 * - First row is auto-skipped if column A doesn't look like a known SKU
 *   pattern — common case where the file has a header «Артикул | Цена».
 * - SKU matching is case-sensitive — products.sku in the catalog is
 *   case-exact, so the file must match.
 * - Empty rows are skipped silently.
 * - Rows with a SKU that doesn't exist are counted as «skipped» and
 *   surfaced in the preview / notes for admin to investigate.
 * - Rows where the new price equals the old price are ALSO counted
 *   as «skipped» (no-op) so the apply step doesn't bump
 *   price_updated_at pointlessly.
 */
final class PriceImportService
{
    /**
     * Parse an uploaded XLSX into a list of [sku, price] candidates.
     *
     * @return list<array{sku: string, price: float, row: int}>
     *
     * @throws \RuntimeException when the file isn't a readable spreadsheet
     */
    public function parse(string $absolutePath): array
    {
        try {
            /** @var Spreadsheet $book */
            $book = IOFactory::load($absolutePath);
        } catch (Throwable $e) {
            throw new \RuntimeException('Не удалось прочитать файл как XLSX/XLS: '.$e->getMessage(), 0, $e);
        }

        $sheet = $book->getActiveSheet();
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $rowNum = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $sku = null;
            $rawPrice = null;
            $colIdx = 0;
            foreach ($cellIterator as $cell) {
                if ($colIdx === 0) {
                    $sku = trim((string) $cell->getValue());
                } elseif ($colIdx === 1) {
                    $rawPrice = $cell->getValue();
                    break;
                }
                $colIdx++;
            }

            if ($sku === null || $sku === '') {
                continue;
            }

            // First-row header detection — if A1 looks like «Артикул»,
            // «SKU», «Наименование», etc. (no digits at all in a typical
            // header), and we haven't pulled anything yet, skip it.
            if ($rowNum === 1 && ! preg_match('/\d/u', $sku)) {
                continue;
            }

            $price = $this->parsePrice($rawPrice);
            if ($price === null) {
                continue;
            }

            $rows[] = [
                'sku' => $sku,
                'price' => $price,
                'row' => $rowNum,
            ];
        }

        return $rows;
    }

    /**
     * Compare parsed rows against the catalog. Returns matched (will
     * update), no-op (price unchanged), and not-found buckets.
     *
     * @param list<array{sku: string, price: float, row: int}> $parsed
     * @return array{
     *   matched: list<array{sku: string, old_price: ?float, new_price: float, name: string, product_id: int}>,
     *   noop: list<array{sku: string, price: float}>,
     *   not_found: list<array{sku: string, price: float, row: int}>
     * }
     */
    public function buildPreview(array $parsed): array
    {
        if ($parsed === []) {
            return ['matched' => [], 'noop' => [], 'not_found' => []];
        }

        $skus = array_unique(array_column($parsed, 'sku'));

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->whereIn('sku', $skus)
            ->get(['id', 'sku', 'name', 'price'])
            ->keyBy('sku');

        $matched = [];
        $noop = [];
        $notFound = [];

        foreach ($parsed as $row) {
            $product = $products->get($row['sku']);
            if ($product === null) {
                $notFound[] = $row;

                continue;
            }

            $oldPrice = $product->price !== null ? (float) $product->price : null;
            if ($oldPrice !== null && abs($oldPrice - $row['price']) < 0.005) {
                $noop[] = ['sku' => $row['sku'], 'price' => $row['price']];

                continue;
            }

            $matched[] = [
                'sku' => $row['sku'],
                'old_price' => $oldPrice,
                'new_price' => $row['price'],
                'name' => $product->name,
                'product_id' => $product->id,
            ];
        }

        return ['matched' => $matched, 'noop' => $noop, 'not_found' => $notFound];
    }

    /**
     * Commit a preview to DB. Updates products + logs to PriceImport
     * inside a transaction so a mid-flight failure rolls everything
     * back atomically.
     *
     * @param array{
     *   matched: list<array{sku: string, old_price: ?float, new_price: float, name: string, product_id: int}>,
     *   noop: list<array{sku: string, price: float}>,
     *   not_found: list<array{sku: string, price: float, row: int}>
     * } $preview
     */
    public function apply(array $preview, string $fileName, ?int $userId): PriceImport
    {
        return DB::transaction(function () use ($preview, $fileName, $userId) {
            foreach ($preview['matched'] as $row) {
                // Use Product::find + save so the booted() saving hook
                // (which stamps price_updated_at when price isDirty)
                // fires. A raw UPDATE would bypass it.
                $product = Product::query()->find($row['product_id']);
                if ($product === null) {
                    continue;
                }
                $product->price = $row['new_price'];
                $product->save();
            }

            return PriceImport::create([
                'file_name' => $fileName,
                'rows_processed' => count($preview['matched']) + count($preview['noop']) + count($preview['not_found']),
                'rows_updated' => count($preview['matched']),
                'rows_skipped' => count($preview['noop']) + count($preview['not_found']),
                'imported_by' => $userId,
                'notes' => [
                    'noop_count' => count($preview['noop']),
                    'not_found' => array_map(fn ($r) => $r['sku'], $preview['not_found']),
                ],
            ]);
        });
    }

    /**
     * «12 500», «12,500.00 ₸», «12500.50 KZT», 12500 — all should yield
     * 12500.50 or 12500.00. Strips currency symbols, spaces, NBSP and
     * non-numeric noise. Detects both comma and dot as decimal
     * separator (Russian-locale files use comma).
     */
    private function parsePrice(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        $str = trim((string) $raw);
        if ($str === '') {
            return null;
        }

        // Strip currency suffixes / NBSP / spaces, keep digits + . + ,
        $cleaned = preg_replace('/[^\d.,\-]/u', '', $str) ?? '';
        if ($cleaned === '') {
            return null;
        }

        // If both . and , are present, assume the LAST one is decimal
        // separator (handles «12,500.00» — comma is thousands, dot is
        // decimal — AND «12.500,00» — dot is thousands, comma is decimal).
        $hasComma = str_contains($cleaned, ',');
        $hasDot = str_contains($cleaned, '.');
        if ($hasComma && $hasDot) {
            $lastComma = (int) strrpos($cleaned, ',');
            $lastDot = (int) strrpos($cleaned, '.');
            if ($lastComma > $lastDot) {
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                $cleaned = str_replace(',', '', $cleaned);
            }
        } elseif ($hasComma) {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
