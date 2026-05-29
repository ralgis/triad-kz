<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the single `address` text into a clean Schema.org-compatible
 * PostalAddress shape. Three new columns sit next to `address`, which
 * stays — it now carries just the street-level part (улица + дом +
 * корпус + офис), and the locality/postal/country come from their
 * own columns.
 *
 * Why: previously `schema:Organization.address.streetAddress` got
 * the whole «ул. Бродского, 186, ниже пр. Рыскулова г. Алматы
 * Республика Казахстан» dumped into it, with locality / country
 * hardcoded to «Алматы» / «KZ» in the partial. Google parses that,
 * but a clean structured address gives stronger local-SEO signals
 * (Knowledge Graph match, Maps card in SERP).
 *
 * `country_code` is intentionally not constrained to an ENUM/CHECK
 * even though we default 'KZ' — the Filament form will show it
 * disabled (locked) so non-admin edits can't drift it. If/when the
 * company opens a branch elsewhere, we unlock the field in the
 * form, not in a schema migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->after('address');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->string('country_code', 2)->default('KZ')->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['city', 'postal_code', 'country_code']);
        });
    }
};
