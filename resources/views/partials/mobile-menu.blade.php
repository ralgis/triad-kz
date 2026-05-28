{{--
    Slide-in mobile drawer. Opened via custom event 'open-mobile-menu'
    dispatched by the burger button — keeps state encapsulated in this
    component and lets us reuse the trigger elsewhere (e.g. a "Меню" link
    in the footer if added later).

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
         class="fixed inset-0 bg-slate-900/50 z-40"
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
         class="fixed top-0 right-0 h-full w-80 max-w-[85vw] bg-white shadow-xl z-50 flex flex-col"
         style="display: none;"
         role="dialog"
         aria-modal="true"
         aria-label="Меню">

        <div class="flex items-center justify-between px-4 h-16 border-b border-slate-200">
            <span class="font-semibold text-slate-800">Меню</span>
            <button @click="open = false; document.documentElement.classList.remove('overflow-hidden')"
                    class="p-2 text-slate-500 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded"
                    aria-label="Закрыть меню">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="Мобильное меню">
            <ul class="space-y-1">
                <li><a href="{{ url('/catalog') }}" class="block px-3 py-3 rounded text-slate-800 hover:bg-slate-50 font-medium">Каталог</a></li>
                <li><a href="{{ url('/blog') }}" class="block px-3 py-3 rounded text-slate-800 hover:bg-slate-50 font-medium">Статьи</a></li>
                <li><a href="{{ url('/about') }}" class="block px-3 py-3 rounded text-slate-800 hover:bg-slate-50 font-medium">О компании</a></li>
                <li><a href="{{ url('/contacts') }}" class="block px-3 py-3 rounded text-slate-800 hover:bg-slate-50 font-medium">Контакты</a></li>
            </ul>
        </nav>

        @if($settings->phone ?? null)
            <div class="border-t border-slate-200 px-4 py-4">
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}"
                   class="block text-center bg-brand-600 hover:bg-brand-700 text-white font-semibold py-3 rounded">
                    Позвонить
                </a>
            </div>
        @endif
    </div>
</div>
