{{--
    Slide-in mobile drawer. Opened via custom event 'open-mobile-menu'
    dispatched by the burger button. Drafting Floor styling: bg-document,
    hard edges, display font nav items.

    Focus is trapped while open and Escape closes the panel. Body scroll
    is locked via tailwind's overflow-hidden on <html>.
--}}
<div x-data="{ open: false }"
     @open-mobile-menu.window="open = true; document.documentElement.classList.add('overflow-hidden')"
     @keydown.escape.window="if(open) { open = false; document.documentElement.classList.remove('overflow-hidden') }"
     class="lg:hidden">

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition.opacity
         @click="open = false; document.documentElement.classList.remove('overflow-hidden')"
         class="fixed inset-0 bg-steel/80 z-40"
         style="display: none;"
         aria-hidden="true"></div>

    {{-- Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-80 max-w-[85vw] bg-document border-l-2 border-edge z-50 flex flex-col"
         style="display: none;"
         role="dialog"
         aria-modal="true"
         aria-label="Меню">

        <div class="flex items-center justify-between px-4 h-14 sm:h-16 border-b-2 border-edge">
            <span class="font-display uppercase tracking-wider text-sm text-steel">Меню</span>
            <button @click="open = false; document.documentElement.classList.remove('overflow-hidden')"
                    class="p-2 border-2 border-edge text-steel hover:bg-steel hover:text-document transition focus:outline-none"
                    aria-label="Закрыть меню">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-3" aria-label="Мобильное меню">
            <ul class="divide-y-2 divide-concrete-dark">
                <li><a href="{{ url('/catalog') }}" class="block px-4 py-4 font-display uppercase tracking-wider text-sm text-steel hover:bg-concrete transition">Каталог</a></li>
                <li><a href="{{ url('/gosts') }}" class="block px-4 py-4 font-display uppercase tracking-wider text-sm text-steel hover:bg-concrete transition">ГОСТы и серии</a></li>
                <li><a href="{{ url('/blog') }}" class="block px-4 py-4 font-display uppercase tracking-wider text-sm text-steel hover:bg-concrete transition">Статьи</a></li>
                <li><a href="{{ url('/about') }}" class="block px-4 py-4 font-display uppercase tracking-wider text-sm text-steel hover:bg-concrete transition">О компании</a></li>
                <li><a href="{{ url('/contacts') }}" class="block px-4 py-4 font-display uppercase tracking-wider text-sm text-steel hover:bg-concrete transition">Контакты</a></li>
            </ul>
        </nav>

        @if($settings->phone ?? null)
            <div class="border-t-2 border-edge p-4 space-y-2">
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}"
                   class="block bg-blueprint-600 text-document font-display uppercase tracking-wider text-sm py-3 text-center hover:bg-blueprint-700 transition">
                    Позвонить · <span class="font-mono normal-case tracking-normal">{{ $settings->phone }}</span>
                </a>
                @php($wa = $settings->whatsappNumbers()[0] ?? null)
                @if($wa)
                    <a href="{{ $wa['wa_url'] }}"
                       class="block border-2 border-edge font-display uppercase tracking-wider text-sm py-3 text-center hover:bg-steel hover:text-document transition">
                        WhatsApp
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
