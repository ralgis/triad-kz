<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Site-wide singleton: brand info, contacts, requisites, analytics IDs.
 *
 * Always row id=1. Use Setting::current() to fetch and create-if-missing.
 * Cache it where it matters (header/footer partials) — see config('triad.settings_cache_ttl').
 */
class Setting extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'site_name',
        'site_tagline',
        'phone',
        'phone_secondary',
        'phone_tertiary',
        'fax',
        'public_email',
        'email_recipient',
        'address',
        'working_hours',
        'skype',
        'map_lat',
        'map_lng',
        'company_legal_name',
        'company_bin',
        'company_iik',
        'company_bank',
        'company_bik',
        'company_kbe',
        'company_legal_address',
        'og_default_image',
        'schema_org_organization',
        'analytics_yandex_id',
        'analytics_google_id',
    ];

    protected function casts(): array
    {
        return [
            'map_lat' => 'decimal:6',
            'map_lng' => 'decimal:6',
            'schema_org_organization' => 'array',
        ];
    }

    /**
     * Get the singleton row, creating it with defaults if missing.
     * Use this everywhere instead of Setting::find(1).
     */
    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], [
            'site_name' => 'ТРИ АД Construction',
        ]);
    }

    public function registerMediaCollections(): void
    {
        // Site logo (header + invoices). Single image.
        $this->addMediaCollection('logo')->singleFile();

        // Default OG fallback for pages without their own image.
        $this->addMediaCollection('og_default')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // nonOptimized() — see Product::registerMediaConversions() for context.
        $this->addMediaConversion('header')->width(200)->height(80)->nonOptimized();
        $this->addMediaConversion('invoice')->width(400)->height(160)->nonOptimized();
    }
}
