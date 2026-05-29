<?php

declare(strict_types=1);

use App\Support\Migrations\SeoFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standards reference table («ГОСТы и Серии»).
 *
 * Legacy site stored these as a static accordion page; every product also
 * carried the standard as free text inside its description WYSIWYG, so
 * misspellings silently broke filtering. New site keeps the reference as
 * a first-class entity with a M2M link from products (see
 * `gost_product`). Admin manages the list in Filament; the public
 * /gosts/ page is a server-rendered mirror of the seed data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gosts', function (Blueprint $table) {
            $table->id();

            // 'gost' = ГОСТ (state standard), 'seriya' = Серия (typical
            // series). Differentiation matters for the public page
            // (different badge) and SEO copy (different intent: ГОСТ is
            // the legal compliance signal, Серия is the engineering
            // catalog reference).
            $table->enum('kind', ['gost', 'seriya']);

            // Human display label as it appears in the accordion header.
            // Examples: 'ГОСТ 8020-90', 'Серия 3.900.1-14 (выпуск 1)'.
            // Kept as a single field rather than computing from a
            // numeric `code` because legacy labels have free-form
            // suffixes («(выпуск 1)», «Выпуск 2») that don't follow a
            // single pattern.
            $table->string('label', 200);

            // Numeric code only — useful for matching products to gosts
            // during legacy import where the description text may say
            // «Серия 3.006.1-2/82(87)» (matches '3.006.1-2.87' fuzzily).
            $table->string('code', 100)->nullable();

            $table->string('slug', 200)->unique();

            // WYSIWYG content shown when the accordion item is expanded
            // on the public /gosts/ page. May contain headings, lists,
            // tables (Серия 3.006.1-2.87 has a 7-row выпуски list).
            $table->longText('description')->nullable();

            // Display order on the public /gosts/ page and in admin.
            $table->unsignedInteger('sort_order')->default(0);

            SeoFields::add($table);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['kind', 'sort_order']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gosts');
    }
};
