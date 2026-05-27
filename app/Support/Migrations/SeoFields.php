<?php

declare(strict_types=1);

namespace App\Support\Migrations;

use Illuminate\Database\Schema\Blueprint;

/**
 * Reusable SEO-fields block for content models (Category, Product, Article, Page).
 *
 * Keeps the 5 SEO columns identical across all content tables so the HasSeo
 * trait and Filament SeoSection work uniformly. If you need to add another
 * SEO column later, add it here and write a single migration that hits every
 * content table — don't drift the schemas.
 */
final class SeoFields
{
    public static function add(Blueprint $table): void
    {
        // Title-tag override. Empty → fall back to model name/title at render.
        $table->string('meta_title')->nullable();

        // Description for SERP snippet + OG. 500 chars is generous — Google
        // truncates at ~160, but we don't want to silently cut user input.
        $table->string('meta_description', 500)->nullable();

        // Optional override of <link rel="canonical">. Empty → url()->current().
        $table->string('canonical_url')->nullable();

        // Force-hide from search engines for individual rows (still public).
        $table->boolean('noindex')->default(false);

        // Hand-crafted JSON-LD for pages where our auto Schema isn't enough.
        // Stored as JSON; cast to array on the model.
        $table->json('structured_data_override')->nullable();
    }
}
