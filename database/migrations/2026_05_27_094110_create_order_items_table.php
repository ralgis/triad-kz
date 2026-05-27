<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Nullable — if a product is later deleted from catalog, we still
            // keep the order history. The snapshot fields below (name/sku/price)
            // preserve what the customer actually bought.
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            // Snapshot fields — copied at checkout time. Allows us to:
            //   1) Render invoices/order pages even if product is deleted/renamed.
            //   2) Show what price the customer agreed to, not current price.
            $table->string('product_name');
            $table->string('product_sku');
            $table->decimal('unit_price', 12, 2);
            $table->string('unit')->default('шт');

            $table->unsignedInteger('qty');
            $table->decimal('line_total', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
