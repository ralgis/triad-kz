<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messenger reachability — per-phone WhatsApp flag + a single
 * Telegram handle.
 *
 * Design decision: WA is keyed off existing phone fields (not a
 * separate `whatsapp_number`) so the admin doesn't have to duplicate
 * the number when WA is on the office line. Edge case where WA lives
 * on a personal mobile that isn't otherwise a callable number → admin
 * adds it as phone_tertiary and ticks the flag.
 *
 * Telegram is stored as the @handle (with the @ included by convention),
 * NOT a phone number — Telegram is a username system, not a number
 * system. Render builds the URL as t.me/{handle without @}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('phone_has_whatsapp')->default(false)->after('phone');
            $table->boolean('phone_secondary_has_whatsapp')->default(false)->after('phone_secondary');
            $table->boolean('phone_tertiary_has_whatsapp')->default(false)->after('phone_tertiary');
            $table->string('telegram_handle')->nullable()->after('skype');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'phone_has_whatsapp',
                'phone_secondary_has_whatsapp',
                'phone_tertiary_has_whatsapp',
                'telegram_handle',
            ]);
        });
    }
};
