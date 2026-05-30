<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log for the Excel price-import feature. Every applied import
 * writes one row here so admin can see what file landed when, by whom,
 * how many SKUs were matched / updated / skipped.
 *
 * Dry-run previews do NOT write to this table — only the final
 * «apply» step does. Keeps the history honest.
 *
 * imported_by points at User (nullable in case the importer was a
 * service-mode CLI invocation later — we want the log even when
 * there's no human attached).
 *
 * notes: JSON catch-all for diagnostics — list of skipped SKUs,
 * parser warnings, column-mapping decisions. Useful when admin asks
 * «why didn't КС10.9 get updated?» — we can read it out of the log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_imports', function (Blueprint $table) {
            $table->id();

            $table->string('file_name');
            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);

            $table->foreignId('imported_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->json('notes')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_imports');
    }
};
