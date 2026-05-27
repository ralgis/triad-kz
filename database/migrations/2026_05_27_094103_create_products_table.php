<?php

declare(strict_types=1);

use App\Support\Migrations\SeoFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();

            // Technical specs — B2B buyers search by these. ГОСТ is the
            // single most important field for SEO; кладу как отдельный
            // string чтобы можно было фильтровать и индексировать.
            $table->string('gost')->nullable();

            // Dimensions as JSON: {"diameter": 1500, "length": 900,
            // "wall_thickness": 80, "weight_kg": 1320}. Keys vary by product
            // type (rings have diameter+length, lotki have length+width).
            $table->json('dimensions')->nullable();

            // Headline weight for the catalog card. Detailed weight goes in
            // dimensions JSON.
            $table->decimal('weight_kg', 8, 2)->nullable();

            // Pricing — see price_visible flag below.
            $table->decimal('price', 12, 2)->nullable();

            // Unit shown next to price: "за шт", "за м.п.", "за м³"
            $table->string('price_unit')->default('за шт');

            // Per-product price visibility. true = show price + Add-to-cart;
            // false = show "Запросить цену" button → contact form modal.
            // Default false: catalog-as-business-card is the safer default
            // for B2B where prices fluctuate by volume.
            $table->boolean('price_visible')->default(false);

            // Order unit (different from display unit): how qty is counted.
            $table->string('unit_for_order')->default('шт');

            $table->longText('description')->nullable();

            $table->boolean('published')->default(false);

            // Shows in "Featured products" section on the homepage.
            $table->boolean('featured')->default(false);

            // Если false — товар отображается но с пометкой "Под заказ" и
            // disabled "В корзину".
            $table->boolean('in_stock')->default(true);

            SeoFields::add($table);

            $table->softDeletes();
            $table->timestamps();

            $table->index('published');
            $table->index('featured');
            $table->index(['published', 'featured']);
            $table->index('in_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
