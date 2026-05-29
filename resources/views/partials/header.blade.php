@php
    use App\Models\Setting;
    $settings ??= Setting::current();
    $phone = $settings->phone;
    // Strip everything but digits and "+" for tel: link — Plesk DEV may not
    // have valid phone yet, so fall back to nothing rather than a broken link.
    $phoneTel = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
@endphp

<header class="bg-white border-b border-slate-200 sticky top-0 z-40">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            {{-- Logo. Image from Settings → logo collection. Falls back to text. --}}
            <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0">
                @php($logo = $settings->getFirstMediaUrl('logo'))
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $settings->site_name }}"
                         class="h-10 lg:h-12 w-auto">
                @else
                    <span class="text-xl lg:text-2xl font-semibold text-brand-700">
                        {{ $settings->site_name }}
                    </span>
                @endif
            </a>

            {{-- Desktop nav. Hidden on mobile, replaced by burger. --}}
            <nav class="hidden lg:flex items-center gap-8" aria-label="Главное меню">
                <a href="{{ url('/catalog') }}" class="text-slate-700 hover:text-brand-600 font-medium">Каталог</a>
                <a href="{{ url('/gosts') }}" class="text-slate-700 hover:text-brand-600 font-medium">ГОСТы и Серии</a>
                <a href="{{ url('/blog') }}" class="text-slate-700 hover:text-brand-600 font-medium">Статьи</a>
                <a href="{{ url('/about') }}" class="text-slate-700 hover:text-brand-600 font-medium">О компании</a>
                <a href="{{ url('/contacts') }}" class="text-slate-700 hover:text-brand-600 font-medium">Контакты</a>
            </nav>

            <div class="flex items-center gap-4">
                {{-- Phone — primary conversion path on B2B. Visible from sm+. --}}
                @if($phone)
                    <a href="tel:{{ $phoneTel }}" class="hidden sm:inline-flex items-center gap-2 text-slate-800 hover:text-brand-600 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75a3 3 0 013-3h2.25a1.5 1.5 0 011.485 1.273l.682 4.092a1.5 1.5 0 01-.71 1.535l-1.97 1.181a16.5 16.5 0 008.108 8.108l1.18-1.97a1.5 1.5 0 011.536-.71l4.092.682A1.5 1.5 0 0123.25 19.5v2.25a3 3 0 01-3 3H17.25C8.85 24.75 0 15.9 0 7.5V6.75z"/>
                        </svg>
                        <span>{{ $phone }}</span>
                    </a>
                @endif

                {{-- Cart icon — placeholder, wired up in Phase 2.3. --}}
                <a href="{{ url('/cart') }}" class="text-slate-700 hover:text-brand-600" aria-label="Корзина">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                    </svg>
                </a>

                {{-- Burger — mobile only. --}}
                <button @click="$dispatch('open-mobile-menu')"
                        class="lg:hidden inline-flex items-center justify-center p-2 text-slate-700 hover:text-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded"
                        aria-label="Открыть меню">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

@include('partials.mobile-menu')
