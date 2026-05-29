<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renames `products.weight_kg` to `products.weight_t` and widens
 * precision to 3 decimals.
 *
 * Why: ЖБИ industry measures product weight in metric tonnes. Legacy
 * descriptions use «0.68 тн.» / «1.275 тн.». Storing in kg forced a
 * ×1000 conversion on every import and a /1000 display on every page,
 * and the public catalog shows «Вес: 680 кг» instead of the
 * idiomatic «Вес: 0.68 т» that B2B buyers expect. Switching to
 * tonnes keeps the data identical to the source and removes the
 * conversion logic.
 *
 * 6,3 precision covers 0.001 tonne (1 kg, smallest reasonable unit)
 * up to 999.999 tonnes (well beyond anything we'd produce).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('weight_kg', 'weight_t');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight_t', 6, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight_t', 8, 2)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('weight_t', 'weight_kg');
        });
    }
};
