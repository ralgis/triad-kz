<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
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

    /**
     * Ordered weekday keys. Keep mon→sun (ISO) — not sun→sat — so
     * the standard «Пн-Пт» grouping is one consecutive slice.
     *
     * @var array<string, string>
     */
    public const DAYS = [
        'mon' => 'Понедельник',
        'tue' => 'Вторник',
        'wed' => 'Среда',
        'thu' => 'Четверг',
        'fri' => 'Пятница',
        'sat' => 'Суббота',
        'sun' => 'Воскресенье',
    ];

    /** @var array<string, string> */
    public const DAYS_SHORT = [
        'mon' => 'Пн', 'tue' => 'Вт', 'wed' => 'Ср', 'thu' => 'Чт',
        'fri' => 'Пт', 'sat' => 'Сб', 'sun' => 'Вс',
    ];

    /** Schema.org openingHours abbreviations. */
    public const DAYS_SCHEMA = [
        'mon' => 'Mo', 'tue' => 'Tu', 'wed' => 'We', 'thu' => 'Th',
        'fri' => 'Fr', 'sat' => 'Sa', 'sun' => 'Su',
    ];

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
        'city',
        'postal_code',
        'country_code',
        'working_hours',
        'special_days',
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
            'working_hours' => 'array',
            'special_days' => 'array',
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
            'city' => 'Алматы',
            'country_code' => 'KZ',
        ]);
    }

    /**
     * Schema.org PostalAddress as a plain array — used by both the
     * Organization and LocalBusiness JSON-LD partials, so the
     * structured-address shape is computed in one place.
     *
     * @return array<string, string>|null
     */
    public function postalAddress(): ?array
    {
        if (! $this->address && ! $this->city) {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $this->address ?: null,
            'addressLocality' => $this->city ?: null,
            'postalCode' => $this->postal_code ?: null,
            'addressCountry' => $this->country_code ?: null,
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

    // ---- Working-hours helpers ----

    /**
     * Today's open/close window, honoring special_days overrides
     * first then the regular weekday schedule. null = closed.
     *
     * @return array{from:string,to:string,note?:string}|null
     */
    public function todaysHours(?CarbonImmutable $when = null): ?array
    {
        $when ??= now()->toImmutable();

        $special = collect($this->special_days ?? [])
            ->first(fn ($d) => ($d['date'] ?? null) === $when->toDateString());

        if ($special !== null) {
            if (($special['status'] ?? 'closed') === 'closed') {
                return null;
            }

            $from = $special['from'] ?? null;
            $to = $special['to'] ?? null;
            if ($from === null || $to === null) {
                return null;
            }

            return ['from' => $from, 'to' => $to, 'note' => $special['note'] ?? null];
        }

        $dayKey = strtolower($when->format('D'));
        $today = collect($this->working_hours ?? [])
            ->first(fn ($d) => ($d['day'] ?? null) === $dayKey);

        if ($today === null || ! ($today['is_open'] ?? false)) {
            return null;
        }

        $from = $today['from'] ?? null;
        $to = $today['to'] ?? null;
        if ($from === null || $to === null) {
            return null;
        }

        return ['from' => $from, 'to' => $to];
    }

    public function isOpenNow(?CarbonImmutable $when = null): bool
    {
        $when ??= now()->toImmutable();
        $today = $this->todaysHours($when);
        if ($today === null) {
            return false;
        }

        $time = $when->format('H:i');

        return $time >= $today['from'] && $time < $today['to'];
    }

    /**
     * Group consecutive same-hours days into compact lines for
     * display: «Пн–Пт 09:00–18:00», «Сб 10:00–15:00», «Вс выходной».
     *
     * @return list<string>
     */
    public function workingHoursLines(): array
    {
        $byDay = collect($this->working_hours ?? [])->keyBy('day');
        $groups = [];
        $current = null;

        foreach (array_keys(self::DAYS) as $day) {
            $entry = $byDay->get($day);
            $key = ($entry && ($entry['is_open'] ?? false))
                ? ($entry['from'] ?? '?').'-'.($entry['to'] ?? '?')
                : 'closed';

            if ($current !== null && $current['key'] === $key) {
                $current['days'][] = $day;
            } else {
                if ($current !== null) {
                    $groups[] = $current;
                }
                $current = ['key' => $key, 'days' => [$day]];
            }
        }
        if ($current !== null) {
            $groups[] = $current;
        }

        return array_map(function (array $g): string {
            $first = $g['days'][0];
            $last = end($g['days']);
            $range = $first === $last
                ? self::DAYS_SHORT[$first]
                : self::DAYS_SHORT[$first].'–'.self::DAYS_SHORT[$last];

            return $g['key'] === 'closed'
                ? "{$range}: выходной"
                : "{$range}: ".str_replace('-', '–', $g['key']);
        }, $groups);
    }

    /**
     * Schema.org openingHours strings — «Mo-Fr 09:00-18:00». Same
     * grouping logic as the display lines, but in the formal format
     * Google expects in LocalBusiness JSON-LD.
     *
     * @return list<string>
     */
    public function workingHoursForSchema(): array
    {
        $byDay = collect($this->working_hours ?? [])->keyBy('day');
        $groups = [];
        $current = null;

        foreach (array_keys(self::DAYS) as $day) {
            $entry = $byDay->get($day);
            $key = ($entry && ($entry['is_open'] ?? false))
                ? ($entry['from'] ?? '?').'-'.($entry['to'] ?? '?')
                : null;

            if ($key === null) {
                if ($current !== null) {
                    $groups[] = $current;
                    $current = null;
                }

                continue;
            }

            if ($current !== null && $current['key'] === $key) {
                $current['days'][] = $day;
            } else {
                if ($current !== null) {
                    $groups[] = $current;
                }
                $current = ['key' => $key, 'days' => [$day]];
            }
        }
        if ($current !== null) {
            $groups[] = $current;
        }

        return array_map(function (array $g): string {
            $first = $g['days'][0];
            $last = end($g['days']);
            $range = $first === $last
                ? self::DAYS_SCHEMA[$first]
                : self::DAYS_SCHEMA[$first].'-'.self::DAYS_SCHEMA[$last];

            return "{$range} {$g['key']}";
        }, $groups);
    }
}
