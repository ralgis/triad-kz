<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * IndexNow key — random 32-char hex stored in settings + served at
 * /<key>.txt for search-engine ownership verification.
 *
 * Auto-generated on migration; admin can rotate via Settings UI.
 * Key requirements per IndexNow spec: 8-128 chars, alphanumeric or
 * hyphen, no slashes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('indexnow_key', 128)->nullable()->after('analytics_enabled');
        });

        // Seed a key for the singleton row so it Just Works after
        // deploy. Admin can rotate via the form.
        DB::table('settings')
            ->where('id', 1)
            ->whereNull('indexnow_key')
            ->update(['indexnow_key' => Str::random(32)]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('indexnow_key');
        });
    }
};
