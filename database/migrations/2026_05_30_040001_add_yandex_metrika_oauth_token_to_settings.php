<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OAuth token for Yandex Metrika Reports API access (popular-articles
 * sort feature). Stored separately from analytics_yandex_id (counter
 * ID, which is public-safe) — token IS sensitive.
 *
 * Encrypted at rest via Eloquent cast on the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('yandex_metrika_oauth_token')->nullable()->after('analytics_google_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('yandex_metrika_oauth_token');
        });
    }
};
