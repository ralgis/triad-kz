<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 P1: M2M pivots replacing the tags-pattern (see PLAN.md §3.4).
 *
 * Three entity-aware relations:
 * - article_product: article ↔ catalog product. Drives «С этим товаром
 *   покупают» blocks + Article.about[] schema entries.
 * - article_gost: article ↔ Gost/serии standard. Drives ГОСТ-pill
 *   metadata in byline + Article.about[].
 * - article_category: article ↔ catalog Category (NOT BlogCategory).
 *   Cross-links article into product navigation breadcrumbs.
 *
 * Naming: singular_singular alphabetical (project convention,
 * migrations/CLAUDE.md §2).
 *
 * onDelete cascade everywhere — pivots are link tables, they don't
 * own data. When a Product is deleted, its article-links go too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_product', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['article_id', 'product_id']);
            $table->index(['product_id', 'article_id']);
        });

        Schema::create('article_gost', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('gost_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['article_id', 'gost_id']);
            $table->index(['gost_id', 'article_id']);
        });

        Schema::create('article_category', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['article_id', 'category_id']);
            $table->index(['category_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category');
        Schema::dropIfExists('article_gost');
        Schema::dropIfExists('article_product');
    }
};
