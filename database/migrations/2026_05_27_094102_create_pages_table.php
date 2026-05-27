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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();

            // Pages like /contacts/ are special — they render with custom
            // controllers that mix CMS content with site-wide data
            // (contact form, map, requisites). The template column lets the
            // controller decide which view to render. NULL = default.
            $table->string('template')->nullable();

            SeoFields::add($table);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
