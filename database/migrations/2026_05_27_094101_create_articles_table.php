<?php

declare(strict_types=1);

use App\Support\Migrations\SeoFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();

            // Short summary for listings and meta-description fallback.
            $table->string('excerpt', 500)->nullable();

            $table->longText('content');

            // NULL = draft. Set to a past timestamp = published.
            // Future timestamp = scheduled (rendered to non-admins after that time).
            $table->timestamp('published_at')->nullable();

            SeoFields::add($table);

            $table->softDeletes();
            $table->timestamps();

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
