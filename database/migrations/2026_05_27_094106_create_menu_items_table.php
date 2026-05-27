<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');

            // Polymorphic: either point to a model row OR to an external URL.
            // Exactly one of (linkable_*, url) is populated — enforced by model.
            $table->nullableMorphs('linkable');
            $table->string('url')->nullable();

            // header / footer / footer-secondary / mobile-bottom-bar etc.
            // String, not enum, чтобы можно было свободно добавлять секции.
            $table->string('position')->default('header');

            // Self-reference for nested menus (top-level + dropdowns).
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('menu_items')
                ->cascadeOnDelete();

            $table->unsignedInteger('order')->default(0);
            $table->boolean('open_in_new_tab')->default(false);

            $table->timestamps();

            $table->index(['position', 'parent_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
