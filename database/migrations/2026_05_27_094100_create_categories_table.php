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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            // Self-referential tree. NULL for top-level categories.
            // onDelete=cascade so the whole subtree dies with the parent —
            // safer than restrict (admin would have to clear children manually).
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->longText('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('published')->default(true);

            SeoFields::add($table);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['parent_id', 'order']);
            $table->index('published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
