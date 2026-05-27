<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            // Singleton — always row id=1. See Setting::current() on the model.
            $table->id();

            // --- Brand ---
            $table->string('site_name')->default('ТРИ АД Construction');
            $table->string('site_tagline')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('phone_tertiary')->nullable();
            $table->string('fax')->nullable();
            $table->string('public_email')->nullable();   // info@triad.kz
            $table->string('email_recipient')->nullable(); // ravacom@mail.ru — куда падают заявки
            $table->string('address')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('skype')->nullable();
            $table->decimal('map_lat', 9, 6)->nullable();
            $table->decimal('map_lng', 9, 6)->nullable();

            // --- Legal requisites (для счёта) ---
            // БИН Казахстана — 12 цифр у юрлиц/ИП.
            $table->string('company_legal_name')->nullable();
            $table->string('company_bin', 12)->nullable();
            $table->string('company_iik')->nullable();  // KZ + 16-18 цифр
            $table->string('company_bank')->nullable();
            $table->string('company_bik', 10)->nullable();
            $table->string('company_kbe', 3)->nullable();
            $table->string('company_legal_address')->nullable();

            // --- SEO defaults ---
            $table->string('og_default_image')->nullable();
            $table->json('schema_org_organization')->nullable();

            // --- Analytics IDs ---
            $table->string('analytics_yandex_id')->nullable();
            $table->string('analytics_google_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
