@php
    use App\Models\Setting;
    $settings ??= Setting::current();

    // Admin can supply a hand-curated JSON-LD via Settings ->
    // schema_org_organization (Filament UI lands in Phase 1.2.b). When
    // present we emit it verbatim — escape hatch for cases the auto
    // shape below doesn't cover (e.g. extra sameAs, awards, founder).
    $override = $settings->schema_org_organization;

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

    $data = is_array($override) && $override !== []
        ? $override
        : array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings->site_name,
            'url' => url('/'),
            'logo' => $settings->getFirstMediaUrl('logo') ?: null,
            'telephone' => $settings->phone ?: null,
            'email' => $settings->public_email ?: null,
            'address' => $settings->company_legal_address ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->company_legal_address,
                'addressLocality' => 'Алматы',
                'addressCountry' => 'KZ',
            ] : null,
            'identifier' => $settings->company_bin ? [
                '@type' => 'PropertyValue',
                'propertyID' => 'БИН',
                'value' => $settings->company_bin,
            ] : null,
            'openingHours' => $openingHours ?: null,
            'specialOpeningHoursSpecification' => $specialDays ?: null,
        ]);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
