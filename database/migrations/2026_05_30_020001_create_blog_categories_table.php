<?php

declare(strict_types=1);

use App\Support\Migrations\SeoFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog rubric/category — hub for topic clusters.
 *
 * Distinct from products' `categories` table (which keeps catalog hierarchy).
 * Blog and catalog categories live in different namespaces and have different
 * SEO funnels — sharing a table would silently couple their slugs.
 *
 * Each blog category serves as a topic-cluster pillar: it has its own
 * description (pillar-grade 300-500 words), cover, and can later host a
 * `pillar_article_id` FK (Phase 2) pointing at the cornerstone article.
 *
 * `order` column drives admin-controlled sort in /blog category-grid. Lower
 * = earlier. No nesting (flat list) — blog rubric hierarchies cause
 * keyword cannibalization across parent/child URLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // WYSIWYG — rendered as the pillar-style intro paragraph on
            // /blog/category/{slug}. Optional, but recommended for SEO.
            $table->longText('description')->nullable();

            $table->unsignedInteger('order')->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('listed')->default(true);

            SeoFields::add($table);

            $table->softDeletes();
            $table->timestamps();

            $table->index('published');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_categories');
    }
};
