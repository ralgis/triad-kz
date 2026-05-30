{{--
    Public layout — used by every customer-facing page (catalog, product,
    cart, checkout, blog, static pages, contacts). Filament admin has its
    own layout via the Filament panel, do not extend this from there.

    Variables consumed (all optional, all have sensible fallbacks):
    - $meta_title         page <title>
    - $meta_description   page meta description
    - $og_image           absolute URL for OG/Twitter card
    - $canonical_url      override (defaults to current URL)
    - $noindex            force noindex even on production
    - $schema_jsonld      pre-rendered JSON-LD <script> block(s)
    - $body_class         extra class on <body>
--}}
<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    @include('partials.head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.schema.organization')
    @include('partials.schema.local-business')
</head>
<body class="min-h-full flex flex-col bg-concrete text-steel antialiased {{ $body_class ?? '' }}">
    {{-- Skip-link for keyboard / screen-reader users. Drafting Floor:
         square edges, blueprint accent, no rounded corners. --}}
    <a href="#main"
       class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50
              focus:bg-blueprint-600 focus:text-document focus:px-4 focus:py-2
              focus:font-display focus:uppercase focus:tracking-wider focus:text-xs">
        Перейти к контенту
    </a>

    @include('partials.header')

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    {{--
        Analytics last — counter init scripts work on full DOM, no need
        to load earlier. Self-no-ops in non-production env, so it's safe
        to keep unconditionally included.
    --}}
    @include('partials.analytics')
</body>
</html>
