<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shifts the catalog default from «hide price, show «Цена по запросу»»
 * to «show price on every product» (decision 2026-05-31).
 *
 * Three related changes in one migration:
 *
 * 1. price_visible: UPDATE existing rows to true everywhere, then
 *    ALTER default to true. New products inherit visibility without
 *    the admin remembering to flip the toggle.
 *
 * 2. price_updated_at: new timestamp column tracking when the price
 *    was last meaningfully changed. Distinct from updated_at (which
 *    flips on any save). Drives the «обновлено N дней назад» freshness
 *    badge on product cards.
 *
 * 3. Seed price_updated_at = now() for all existing rows — at deploy
 *    time we «actualize» the entire catalog. Subsequent edits via
 *    Filament (or the upcoming Excel import) update the timestamp via
 *    a Product model hook (see app/Models/Product.php::booted()).
 *
 * Rollback: down() restores the false default and drops the column.
 * It does NOT flip existing rows' price_visible back to false — those
 * are admin-controlled state that wasn't tracked reversibly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('price_updated_at')->nullable()->after('price_visible');
        });

        DB::table('products')->update([
            'price_visible' => true,
            'price_updated_at' => now(),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('price_visible')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('price_visible')->default(false)->change();
            $table->dropColumn('price_updated_at');
        });
    }
};
