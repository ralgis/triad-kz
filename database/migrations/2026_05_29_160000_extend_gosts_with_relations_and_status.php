<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the second-tier metadata to the standards reference so it can
 * carry the full picture: official document title, Kazakhstan
 * effective-until date, replacement chain, and Серия → ГОСТ link.
 *
 * Why each field:
 *  - title: the official name of the standard («Конструкции бетонные…
 *    для колодцев…»). Goes into <title>/<h1>/Schema.org alongside the
 *    short label so SEO ranks for the human-readable phrase, not just
 *    the code.
 *  - introduced_at: date the standard was put into effect. Useful for
 *    «Введён 15 января 1990 г.» captions and ordering.
 *  - is_current: whether the standard is currently in force in
 *    Kazakhstan. Three of the five seeded records (8020-90, 13579-78,
 *    3.900-3) are formally superseded but kept because manufacturers
 *    still mark products by them.
 *  - effective_in_kz_until: when the standard was withdrawn in KZ
 *    (NOT in RU — those dates differ). Displayed on the public page so
 *    buyers see «действовал до 27.09.2021» plainly.
 *  - superseded_by_id: self-FK to the replacement record (when one
 *    exists in our reference). Lets the public page link «Заменён ГОСТ
 *    8020-2016» directly.
 *  - superseded_note: free-text fallback when the replacement isn't in
 *    the reference («Заменён ГОСТ 8020-68» — historical context, no
 *    record needed). Displayed if superseded_by_id is null.
 *  - relates_to_gost_id: self-FK from a Серия to its parent ГОСТ
 *    (e.g. Серия 3.900.1-14 → ГОСТ 8020-90). Most series have one;
 *    Серия 3.006.1-2.87 is standalone (no parent in our set), so the
 *    column is nullable. The reverse lookup (parent ГОСТ → list of
 *    series) goes through a Gost::series() accessor on the model.
 *
 * Schema-only — data backfill lives in GostsSeeder (re-run after this
 * migration to populate the new columns and create the two new
 * records for the current Kazakhstan editions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gosts', function (Blueprint $table) {
            $table->string('title', 500)->nullable()->after('label');

            $table->date('introduced_at')->nullable()->after('description');
            $table->boolean('is_current')->default(true)->after('introduced_at');
            $table->date('effective_in_kz_until')->nullable()->after('is_current');

            $table->foreignId('superseded_by_id')
                ->nullable()
                ->after('effective_in_kz_until')
                ->constrained('gosts')
                ->nullOnDelete();

            $table->string('superseded_note')->nullable()->after('superseded_by_id');

            $table->foreignId('relates_to_gost_id')
                ->nullable()
                ->after('superseded_note')
                ->constrained('gosts')
                ->nullOnDelete();

            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::table('gosts', function (Blueprint $table) {
            $table->dropForeign(['superseded_by_id']);
            $table->dropForeign(['relates_to_gost_id']);

            $table->dropIndex(['is_current']);

            $table->dropColumn([
                'title',
                'introduced_at',
                'is_current',
                'effective_in_kz_until',
                'superseded_by_id',
                'superseded_note',
                'relates_to_gost_id',
            ]);
        });
    }
};
