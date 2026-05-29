<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-referencing M2M pivot for the «С этим товаром покупают»
 * (complementary products) block on product detail pages.
 *
 * Different from «Также в этой категории» — that block is
 * auto-derived (same-category siblings) at render time and needs no
 * storage. This pivot stores admin-curated complementary pairs
 * across categories («кольцо КС10.6» + «плита перекрытия ПП10-1»
 * + «опорное кольцо КО6» — different categories, same engineering
 * solution).
 *
 * The relation is conceptually symmetric (if A is complementary to
 * B, B is complementary to A), but we store directed rows and keep
 * both directions in sync via Product::syncComplementarySymmetric()
 * called from the Filament form. That keeps the read path a plain
 * BelongsToMany without UNION trickery, and lets the admin remove a
 * link from either side.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complementary_products', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('complementary_product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Admin-controlled display order on the public block.
            // SmallInt because 4-8 items per product is the practical
            // ceiling — three digits is plenty of headroom.
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->primary(['product_id', 'complementary_product_id']);
            $table->index('complementary_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complementary_products');
    }
};
