<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 P1: topic-cluster + featured + FAQ infra on articles.
 *
 * - article_type: pillar / guide / comparison / news / case. Cast as enum
 *   on the model but stored as varchar for SQLite portability (project
 *   convention — see database/migrations/CLAUDE.md §7).
 * - is_pillar / pillar_id pair: disambiguates «standalone article» from
 *   «cluster of a pillar» from «is the pillar». pillar_id self-ref with
 *   onDelete set-null so deleting a pillar doesn't cascade-kill its
 *   clusters (they just lose their pillar attribution and admin can
 *   re-link).
 * - featured: surfaces on /blog hero strip
 * - pinned_until: sticky in category listing until the timestamp passes
 * - toc_enabled: opt-out for short articles where TOC adds noise
 * - faq: JSON repeater of {question, answer} — only filled when real Q&A
 *   exists (see PLAN.md §7.5; not on every pillar by default)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Default 'guide' is intentional — most B2B-ЖБИ content
            // starts as a guide; admin sets pillar/news explicitly.
            $table->string('article_type', 20)->default('guide')->after('blog_category_id');

            $table->boolean('is_pillar')->default(false)->after('article_type');

            $table->foreignId('pillar_id')
                ->nullable()
                ->after('is_pillar')
                ->constrained('articles')
                ->onDelete('set null');

            $table->boolean('featured')->default(false)->after('updated_content_at');
            $table->timestamp('pinned_until')->nullable()->after('featured');
            $table->boolean('toc_enabled')->default(true)->after('pinned_until');

            $table->json('faq')->nullable()->after('toc_enabled');

            $table->index('featured');
            $table->index('article_type');
            $table->index('is_pillar');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['featured']);
            $table->dropIndex(['article_type']);
            $table->dropIndex(['is_pillar']);
            $table->dropConstrainedForeignId('pillar_id');
            $table->dropColumn([
                'article_type',
                'is_pillar',
                'featured',
                'pinned_until',
                'toc_enabled',
                'faq',
            ]);
        });
    }
};
