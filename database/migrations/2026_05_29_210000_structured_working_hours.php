<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Working-hours field switches from a free-text Textarea
 * («Пн-Пт 9:00-18:00, Сб 10:00-15:00, Вс выходной») to a structured
 * JSON array — one entry per weekday with open/close times. Adds a
 * second JSON column for one-off overrides (holidays, short days).
 *
 * Structured form is necessary because we want to:
 *   - render in Schema.org openingHours / specialOpeningHoursSpecification
 *   - power an «открыто сейчас» / «закроется через…» pill on the
 *     contacts page using the visitor's current time
 *   - group consecutive same-hours days for a clean «Пн-Пт 09:00-18:00»
 *     display string without the admin typing it by hand
 *
 * Storage formats (decided in conversation):
 *   working_hours JSON: list of 7 objects keyed by `day` (mon..sun)
 *     [{day:'mon', is_open:true, from:'09:00', to:'18:00'}, ...]
 *   special_days JSON: list of zero-or-more overrides
 *     [{date:'2027-01-01', status:'closed', from:null, to:null, note:'Новый год'}, ...]
 *
 * Existing text «working_hours» column is dropped — Settings is admin-
 * managed and the current value (per the contacts-page screenshot)
 * was effectively empty. Admin re-fills via the 7-row form on first
 * post-deploy save.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('working_hours');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->json('working_hours')->nullable()->after('address');
            $table->json('special_days')->nullable()->after('working_hours');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['working_hours', 'special_days']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->text('working_hours')->nullable()->after('address');
        });
    }
};
