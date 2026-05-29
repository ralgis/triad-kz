{{--
    SEO <head>. Lives apart from the layout so non-app contexts (PDF,
    email) can reuse pieces if needed. Reads from the Settings singleton
    for site-wide defaults; per-page overrides flow in as variables from
    the controller.
--}}
@php
    use App\Models\Setting;
    $settings = Setting::current();
    $title = $meta_title ?? ($settings->site_name ?? config('app.name'));
    $description = $meta_description ?? ($settings->site_tagline ?? '');
    $canonical = $canonical_url ?? url()->current();
    $og = $og_image ?? $settings->getFirstMediaUrl('og_default');
    $forceNoindex = $noindex ?? false;
    $isProd = app()->environment('production');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>{{ $title }}</title>

{{--
    Inline SVG placeholder — letter Т on the brand-dark background. Drop
    a real favicon.ico / favicon.png into public/ and uncomment the lines
    below once the client supplies one.
--}}
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='12' fill='%23253142'/%3E%3Ctext x='50' y='72' font-family='Arial,sans-serif' font-size='66' font-weight='700' text-anchor='middle' fill='white'%3E%D0%A2%3C/text%3E%3C/svg%3E">
@if($description !== '')
    <meta name="description" content="{{ $description }}">
@endif
<link rel="canonical" href="{{ $canonical }}">

{{--
    Dev environment AND any opt-in $noindex (admin can flag a page) MUST
    suppress indexing. The HTTP X-Robots-Tag header in
    EnsureNoindexInNonProd is the load-bearing layer; this meta tag is
    belt-and-braces for crawlers that ignore headers (yandex sometimes,
    older bots always).
--}}
@if(! $isProd || $forceNoindex)
    <meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:title" content="{{ $title }}">
@if($description !== '')
    <meta property="og:description" content="{{ $description }}">
@endif
@if($og)
    <meta property="og:image" content="{{ $og }}">
@endif
<meta property="og:type" content="{{ $og_type ?? 'website' }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="{{ $settings->site_name }}">

@isset($schema_jsonld)
    {!! $schema_jsonld !!}
@endisset
