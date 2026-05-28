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
</head>
<body class="min-h-full flex flex-col bg-white text-slate-800 antialiased {{ $body_class ?? '' }}">
    {{-- Skip-link for keyboard / screen-reader users. --}}
    <a href="#main"
       class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50
              focus:bg-brand-600 focus:text-white focus:px-4 focus:py-2 focus:rounded">
        Перейти к контенту
    </a>

    @include('partials.header')

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
