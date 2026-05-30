<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 P0: add blog_category + reading-time + content-date freshness
 * fields to articles. Foundations for the 2026 blog SEO spec —
 * docs/blog-seo-plan-2026.md §2.
 *
 * No `author_id` here. The publisher-only attribution path (Organization
 * as Article.author + Article.publisher) is what we ship for now —
 * realistic for a B2B-ЖБИ blog where one verified expert with a real
 * bio doesn't exist yet, and a fake byline is worse than no byline.
 *
 * blog_category_id is nullable initially (existing rows have nothing to
 * assign) — Filament forces it on create. A backfill + NOT NULL flip
 * lands in Phase 2 once admin populates rubrics.
 *
 * onDelete restrict on blog_category_id: deleting a rubric mid-flight
 * while articles reference it is almost always a mistake. Soft-delete
 * lives at app level (BlogCategory has SoftDeletes) so the FK rarely
 * fires anyway, but we want the safety net.
 *
 * updated_content_at is intentionally NOT auto-touched on save (see
 * Article::recomputeReadingStats which only flips word_count). Google's
 * Helpful Content Update penalises fake freshness. Filament exposes a
 * manual «Пометить обновлённой» action; the form field itself is
 * read-only.
 *
 * Composite index column order is `[blog_category_id, published_at]`
 * — the hot listing query filters by equality on category and ranges
 * over published_at, so equality-first ordering lets the planner
 * prefix-match the index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('blog_category_id')
                ->nullable()
                ->after('id')
                ->constrained('blog_categories')
                ->onDelete('restrict');

            $table->string('subtitle', 300)->nullable()->after('title');

            $table->unsignedSmallInteger('reading_minutes')->nullable()->after('content');
            $table->unsignedMediumInteger('word_count')->nullable()->after('reading_minutes');

            $table->timestamp('updated_content_at')->nullable()->after('published_at');

            $table->index(['blog_category_id', 'published_at'], 'articles_cat_pub_idx');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_cat_pub_idx');
            $table->dropConstrainedForeignId('blog_category_id');
            $table->dropColumn([
                'subtitle',
                'reading_minutes',
                'word_count',
                'updated_content_at',
            ]);
        });
    }
};
