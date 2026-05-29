<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `listed` boolean to both products and categories so admin can
 * remove an item from catalog navigation while keeping its public URL
 * working (for direct sharing of legacy / inventory-internal links).
 *
 * Distinguishes from existing `published`:
 *   - published=false → 404 to everyone (still-in-progress draft).
 *   - published=true + listed=false → page works on direct URL but
 *     does NOT appear in catalog listings / sitemap. Search engines
 *     can still find it via external backlinks — that's allowed,
 *     it's NOT cloaking (same content served to bots and users).
 *
 * Default true so existing rows continue to appear in the catalog
 * exactly as before. Admin opts items out individually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('listed')->default(true)->after('in_stock');
            $table->index('listed');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('listed')->default(true)->after('published');
            $table->index('listed');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['listed']);
            $table->dropColumn('listed');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['listed']);
            $table->dropColumn('listed');
        });
    }
};
