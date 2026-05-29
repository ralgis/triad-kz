<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the two legacy columns now fully replaced by structured
 * storage:
 *  - `gost` (text) → `gosts` M2M pivot via Gost reference table
 *  - `dimensions` (JSON) → 11 typed spec columns from migration 0180
 *
 * Both have been transitional fallbacks since their replacements
 * landed (c4e3ecec / 9b1942d0). All read paths now hit the typed
 * data — drop is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gost', 'dimensions']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('gost')->nullable();
            $table->json('dimensions')->nullable();
        });
    }
};
