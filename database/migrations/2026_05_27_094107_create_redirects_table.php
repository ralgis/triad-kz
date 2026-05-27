<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();

            // Source path (where the user hit a 404). NOT a full URL —
            // host-relative, leading slash, no query string.
            // E.g. `/%d0%b1%d0%b5%d1%82%d0%be%d0%bd-koltsa/` (old WP slug).
            $table->string('from')->unique();

            // Destination path (relative) or full URL (for external).
            $table->string('to');

            // 301 (default — permanent) or 302 (temporary).
            $table->unsignedSmallInteger('status')->default(301);

            // Counter for observability — how often did this redirect fire?
            // Tells us which old URLs still pull traffic and which are dead.
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();

            $table->timestamps();

            $table->index('from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
