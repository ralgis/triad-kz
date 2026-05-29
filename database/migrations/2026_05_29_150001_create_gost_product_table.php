<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M2M pivot: products ↔ gosts.
 *
 * One product typically references either «ГОСТ X», «Серия Y», or both
 * (e.g. фундаментные блоки = «ГОСТ 13579-78» only; кольца колодца =
 * «ГОСТ 8020-90» + «Серия 3.900.1-14»). Pivot lets the same gost
 * reference appear on many products without duplicating it.
 *
 * Both sides cascade on delete: dropping a product wipes its pivot
 * rows; dropping a gost (rare — admin-only action) wipes all product
 * links. Soft-deletes on both ends are honored by the relation queries
 * automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gost_product', function (Blueprint $table) {
            $table->foreignId('gost_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['gost_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gost_product');
    }
};
