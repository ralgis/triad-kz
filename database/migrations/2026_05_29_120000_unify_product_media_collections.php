<?php

declare(strict_types=1);

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merges the per-purpose Product media collections (blueprint / real /
 * gallery) into a single `images` collection. Display order is set by
 * the old collection: blueprint → real → gallery — per client decision
 * 2026-05-29, with the explicit expectation that admins drag-reorder
 * after this runs to put the strongest photo first.
 *
 * Files on disk don't move — TriadPathGenerator keys on model_type so
 * everything stays under storage/app/public/goods/{media_id}/.
 */
return new class extends Migration
{
    public function up(): void
    {
        $productType = Product::class;

        DB::transaction(function () use ($productType) {
            $offsets = [
                'blueprint' => 0,
                'real' => 1_000,
                'gallery' => 2_000,
            ];

            foreach ($offsets as $oldCollection => $base) {
                DB::table('media')
                    ->where('model_type', $productType)
                    ->where('collection_name', $oldCollection)
                    ->orderBy('id')
                    ->each(function (object $row, int $i) use ($base) {
                        DB::table('media')->where('id', $row->id)->update([
                            'collection_name' => 'images',
                            'order_column' => $base + $i,
                        ]);
                    });
            }
        });
    }

    public function down(): void
    {
        // No reversible mapping — once merged we don't have the source
        // collection on the row. Restoring from a pre-migration DB
        // snapshot is the documented rollback path.
    }
};
