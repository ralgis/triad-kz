<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widens `gosts.kind` from ENUM('gost','seriya') to VARCHAR(20) so we
 * can register a third standard type — Стандарт Организации («СТ ТОО»)
 * — without re-altering the enum. Same for any future kinds (СТ РК,
 * ТУ, СНиП) which would otherwise each cost a migration.
 *
 * No data conversion needed: 'gost' / 'seriya' values transfer to
 * VARCHAR identically. Existing index on kind survives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gosts', function (Blueprint $table) {
            $table->string('kind', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('gosts', function (Blueprint $table) {
            $table->enum('kind', ['gost', 'seriya'])->change();
        });
    }
};
