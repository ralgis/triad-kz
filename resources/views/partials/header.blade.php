@php
    use App\Models\Setting;
    $settings ??= Setting::current();
    $phone = $settings->phone;
    $phoneTel = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
    // Tech-strip data: city + БИН + year — like the stamp at the top
    // of a draft sheet. Each piece falls back gracefully so we never
    // emit «· ·» empty separators when Settings is fresh.
    $stripBits = array_filter([
        $settings->city ? mb_strtoupper((string) $settings->city) : null,
        'ЖБИ',
        $settings->company_bin ? 'БИН '.$settings->company_bin : null,
    ]);
@endphp

<header class="bg-document border-b-2 border-edge sticky top-0 z-40">
    {{-- Main bar --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-3 h-14 sm:h-16 lg:h-20">
            <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0 min-w-0">
                @php($logo = $settings->getFirstMediaUrl('logo'))
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $settings->site_name }}"
                         class="h-8 sm:h-10 lg:h-12 w-auto">
                @else
                    <span class="font-display uppercase tracking-wider text-sm sm:text-base lg:text-lg text-steel truncate">
                        {{ $settings->site_name }}
                    </span>
                @endif
            </a>

            <nav class="hidden lg:flex items-center gap-6 xl:gap-8 font-display uppercase tracking-wider text-sm" aria-label="Главное меню">
                <a href="{{ url('/catalog') }}" class="text-steel hover:text-blueprint-600 transition">Каталог</a>
                <a href="{{ url('/gosts') }}" class="text-steel hover:text-blueprint-600 transition">ГОСТы</a>
                <a href="{{ url('/blog') }}" class="text-steel hover:text-blueprint-600 transition">Статьи</a>
                <a href="{{ url('/about') }}" class="text-steel hover:text-blueprint-600 transition">О компании</a>
                <a href="{{ url('/contacts') }}" class="text-steel hover:text-blueprint-600 transition">Контакты</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                @if($phone)
                    <a href="tel:{{ $phoneTel }}"
                       class="hidden sm:inline-flex items-center gap-2 px-3 py-2 border-2 border-edge text-steel hover:bg-steel hover:text-document transition font-mono text-xs sm:text-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <span class="spec-value whitespace-nowrap">{{ $phone }}</span>
                    </a>
                @endif

                <a href="{{ url('/cart') }}"
                   class="p-2 border-2 border-edge text-steel hover:bg-steel hover:text-document transition"
                   aria-label="Корзина">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0z"/></svg>
                </a>

                <button @click="$dispatch('open-mobile-menu')"
                        class="lg:hidden p-2 border-2 border-edge text-steel hover:bg-steel hover:text-document transition focus:outline-none"
                        aria-label="Открыть меню">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Spec strip — like the header band on a draft sheet. Carries
         the locator metadata (city, industry, БИН). --}}
    @if(! empty($stripBits))
        <div class="border-t border-concrete-dark bg-document">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-1.5 sm:py-2">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider truncate">
                    {!! implode(' · ', $stripBits) !!}
                </p>
            </div>
        </div>
    @endif
</header>

@include('partials.mobile-menu')
