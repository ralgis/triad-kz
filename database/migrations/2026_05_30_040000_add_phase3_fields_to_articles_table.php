<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 P2/P3 fields on articles.
 *
 * - how_to_steps: JSON [{name, text, image_id?}, ...] for HowTo schema.
 *   AI-extraction value only; Google removed HowTo SERP rich results
 *   August 2023 (see PLAN.md §7.6). Surfaced via «How to» blocks in
 *   guide-type articles + JSON-LD.
 * - external_sources: JSON [{title, url, accessed_at?, note?}, ...] for
 *   the «Источники» list at the bottom of pillar/guide articles.
 *   rel="external nofollow noopener" on render. E-E-A-T trust signal
 *   for readers (LLM-citation value is folk wisdom — see PLAN.md §12.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->json('how_to_steps')->nullable()->after('faq');
            $table->json('external_sources')->nullable()->after('how_to_steps');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['how_to_steps', 'external_sources']);
        });
    }
};
