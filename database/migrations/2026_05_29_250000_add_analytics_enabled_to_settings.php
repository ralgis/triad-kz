<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the env('production') gate on the public analytics partial
 * with an explicit admin Toggle. env-based gates are silent — easy to
 * forget after cutover that the rule «only on prod» exists. An
 * explicit «Включить аналитику» switch on the Settings page makes the
 * state visible and self-documenting.
 *
 * Default false so the post-cutover first deploy doesn't accidentally
 * start firing counter hits before the admin reviews the IDs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('analytics_enabled')
                ->default(false)
                ->after('analytics_google_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('analytics_enabled');
        });
    }
};
