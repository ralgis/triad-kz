@php
    use App\Models\Setting;
    $settings ??= Setting::current();

    /*
     * LocalBusiness extends Organization with the physical-place
     * signals Google needs for Knowledge Graph / Maps Card / local
     * SERP ranking — geo coordinates, openingHours,
     * specialOpeningHoursSpecification. Emitted alongside
     * Organization (already in layouts/app.blade.php) — Google is
     * happy receiving both, the structured-data parser dedupes by
     * name+url.
     *
     * Skipped when address or geo are missing — without those the
     * LocalBusiness payload is incomplete and Google flags it as a
     * markup error rather than treating it as a hint.
     */
    $address = $settings->postalAddress();
    $hasGeo = $settings->map_lat !== null && $settings->map_lng !== null;
    if ($address === null && ! $hasGeo) {
        return;
    }

    $openingHours = $settings->workingHoursForSchema();
    $specialDays = collect($settings->special_days ?? [])
        ->filter(fn ($d) => ! empty($d['date']))
        ->map(fn ($d) => array_filter([
            '@type' => 'OpeningHoursSpecification',
            'validFrom' => $d['date'],
            'validThrough' => $d['date'],
            'opens' => ($d['status'] ?? '') === 'short' ? ($d['from'] ?? null) : null,
            'closes' => ($d['status'] ?? '') === 'short' ? ($d['to'] ?? null) : null,
            'description' => $d['note'] ?? null,
        ]))
        ->values()
        ->all();

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $settings->site_name,
        'url' => url('/'),
        'logo' => $settings->getFirstMediaUrl('logo') ?: null,
        'image' => $settings->getFirstMediaUrl('logo') ?: null,
        'telephone' => $settings->phone ?: null,
        'email' => $settings->public_email ?: null,
        'address' => $address,
        'geo' => $hasGeo ? [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $settings->map_lat,
            'longitude' => (float) $settings->map_lng,
        ] : null,
        'openingHours' => $openingHours ?: null,
        'specialOpeningHoursSpecification' => $specialDays ?: null,
        // Catalog-as-business-card pricing — no public prices for most
        // SKUs, so the cheap-to-expensive band sits in the upper
        // bracket but stays generic. Update once the bulk of prices
        // are public on the catalog.
        'priceRange' => '$$',
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
