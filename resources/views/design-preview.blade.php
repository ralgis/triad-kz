{{--
    Standalone Drafting Floor preview — bypasses layouts.app so it
    doesn't inherit the legacy white-body + old nav. Only the new
    system shows.

    Will be removed once the design lands in the real public views.
--}}
<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Design Preview — Drafting Floor — ТРИ АД</title>
    <meta name="robots" content="noindex, nofollow">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-concrete text-steel antialiased">

    {{-- Spec strip header — like a draft sheet header --}}
    <header class="border-b-2 border-edge bg-document">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-3 sm:py-4 flex items-center justify-between gap-3">
            <div class="font-display uppercase tracking-wider text-sm sm:text-base lg:text-lg truncate">
                ТРИ АД CONSTRUCTION
            </div>
            <nav class="hidden md:flex gap-6 lg:gap-8 font-display uppercase tracking-wider text-sm">
                <a href="#" class="hover:text-blueprint-600 whitespace-nowrap">Каталог</a>
                <a href="#" class="hover:text-blueprint-600">Статьи</a>
                <a href="#" class="hover:text-blueprint-600">ГОСТы</a>
                <a href="#" class="hover:text-blueprint-600">Контакты</a>
            </nav>
            {{-- Hamburger placeholder for mobile — will be Alpine.js slide-in in Phase B --}}
            <button class="md:hidden p-2 -mr-2 border-2 border-edge" aria-label="Меню">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M3 6h14M3 10h14M3 14h14"/>
                </svg>
            </button>
        </div>
        <div class="border-t border-concrete-dark">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider truncate">
                    ALMATY · ЖБИ · 2008 — НАСТ. · БИН 123456789012
                </p>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="bg-blueprint-grid border-b-2 border-edge">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-24 xl:py-32 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            <div class="lg:col-span-8">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">
                    ━━━━━━━ КАТАЛОГ ЖБИ
                </p>
                {{--
                    Headline scaling — start small on iPhone SE (375px). At
                    text-5xl the word «ЖЕЛЕЗОБЕТОННЫЕ» (14 chars) overflows
                    343px content width. text-[2rem] (32px) keeps it on one
                    line; text-4xl (36px) needs a soft wrap. Tailwind's
                    breakpoint scale: <sm 32px, sm 36px, md 48px, lg 64px,
                    xl 72-80px.
                --}}
                <h1 class="text-[2rem] leading-[1.05] sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl uppercase">
                    Железобетонные<br>изделия<br>
                    <span class="text-blueprint-600">в Алматы</span>
                </h1>
                <p class="mt-6 sm:mt-8 max-w-xl text-base sm:text-lg text-steel-soft leading-relaxed">
                    Производство с 2008 года. Бетонные кольца, ФБС-блоки,
                    плиты перекрытия, опорные подушки, арычные лотки.
                    ГОСТ. Серии. Сертификация.
                </p>
                <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <x-button variant="primary" size="lg" href="#" class="w-full sm:w-auto">Каталог →</x-button>
                    <x-button variant="outline" size="lg" href="#" class="w-full sm:w-auto">Запрос цены</x-button>
                </div>
            </div>

            {{--
                Contact card — primary B2B-ЖБИ conversion path. The phone
                number is the load-bearing element: large mono, tappable
                tel: link, touch target >= 44px. Manager byline adds the
                human face (B2B trust). Messengers + email below for the
                «I'd rather text first» segment. Final CTA «Получить прайс»
                is the highest-conversion ask (PDF download via lead form).

                All copy here is PLACEHOLDER — actual values land from
                Settings (phone / public_email / working_hours / etc.)
                when this gets wired into the real hero in Phase D.
            --}}
            <aside class="lg:col-span-4 bg-document border-2 border-edge self-start">
                {{-- Header strip --}}
                <div class="bg-steel text-document px-5 sm:px-6 py-3 flex items-center justify-between">
                    <p class="font-display uppercase tracking-wider text-xs sm:text-sm">
                        Отдел продаж
                    </p>
                    <span class="font-mono text-[10px] sm:text-xs text-haze uppercase">ONLINE</span>
                </div>

                {{-- Phone block — option B layout. Number as plain mono
                     above, two equal-weight buttons below: tel: and
                     wa.me. Equal weight signals «WA is a peer channel,
                     not an afterthought». WhatsApp button renders only
                     when the primary phone has the WA flag (read from
                     Setting::whatsappNumbers() in real wiring; here we
                     hard-code «yes» for the preview). --}}
                <div class="px-5 sm:px-6 py-5 border-b-2 border-concrete-dark">
                    <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-1">Прямой телефон</p>
                    <p class="font-mono text-xl sm:text-2xl text-steel spec-value leading-tight mb-4">
                        +7 727 000-00-00
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="tel:+77270000000"
                           class="inline-flex items-center justify-center gap-2 px-3 py-3 border-2 border-edge bg-document font-display uppercase tracking-wider text-xs hover:bg-steel hover:text-document transition">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            Позвонить
                        </a>
                        <a href="https://wa.me/77270000000"
                           class="inline-flex items-center justify-center gap-2 px-3 py-3 border-2 border-edge bg-document font-display uppercase tracking-wider text-xs hover:bg-steel hover:text-document transition">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            WhatsApp
                        </a>
                    </div>
                </div>

                {{-- Hours --}}
                <div class="px-5 sm:px-6 py-4 border-b-2 border-concrete-dark">
                    <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-2">Часы работы</p>
                    <dl class="space-y-1 font-mono text-sm">
                        <div class="flex justify-between">
                            <dt class="text-haze">ПН—ПТ</dt><dd class="spec-value">09:00 — 18:00</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-haze">СБ</dt><dd class="spec-value">10:00 — 15:00</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-haze">ВС</dt><dd class="text-haze">выходной</dd>
                        </div>
                    </dl>
                </div>

                {{-- Telegram + Email — second-tier channels. Telegram
                     renders only when telegram_handle is configured;
                     otherwise this section shows only email. --}}
                <div class="px-5 sm:px-6 py-4 border-b-2 border-concrete-dark">
                    <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-3">Telegram + Email</p>
                    <a href="https://t.me/triadkz_sales"
                       class="inline-flex items-center gap-2 px-3 py-2 border-2 border-edge font-mono text-xs uppercase tracking-wider hover:bg-blueprint-600 hover:text-document hover:border-blueprint-600 transition">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                        Telegram
                    </a>
                    <a href="mailto:sales@triadkz.kz"
                       class="block mt-3 font-mono text-xs text-blueprint-600 hover:text-blueprint-700 hover:underline">
                        sales@triadkz.kz
                    </a>
                </div>

                {{-- Final CTA — the high-converting ask for B2B-ЖБИ --}}
                <a href="#"
                   class="block bg-stamp-600 text-document hover:bg-stamp-700 transition px-5 sm:px-6 py-4 text-center font-display uppercase tracking-wider text-sm">
                    Получить прайс →
                </a>
            </aside>
        </div>
    </section>

    {{-- Product card sample --}}
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4 sm:mb-6">
            ━━━━━━━ ПРИМЕР: КАРТОЧКА ТОВАРА
        </p>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl uppercase mb-8 sm:mb-12">Бетонные кольца</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach([
                ['sku' => 'КС10.9', 'name' => 'Бетонное кольцо стеновое', 'dia' => '1000 × 890 мм', 'weight' => '0.85 т', 'volume' => '0.685 м³', 'price' => '12 500'],
                ['sku' => 'КС15.9', 'name' => 'Бетонное кольцо стеновое', 'dia' => '1500 × 890 мм', 'weight' => '1.45 т', 'volume' => '1.156 м³', 'price' => '21 800'],
                ['sku' => 'КС20.9', 'name' => 'Бетонное кольцо стеновое', 'dia' => '2000 × 890 мм', 'weight' => '2.15 т', 'volume' => '1.720 м³', 'price' => '33 400'],
            ] as $p)
                <article class="bg-document border-2 border-edge group hover:translate-y-[-2px] transition">
                    <div class="aspect-square bg-concrete-dark border-b-2 border-edge flex items-center justify-center text-haze relative">
                        <span class="font-mono text-xs">[фото товара]</span>
                        <div class="absolute top-3 right-3 bg-stamp-600 text-document px-2 sm:px-3 py-1 font-mono text-[10px] sm:text-xs uppercase tracking-wider">
                            ГОСТ 8020-90
                        </div>
                    </div>
                    <div class="p-4 sm:p-5">
                        <p class="font-display text-xl sm:text-2xl uppercase tracking-tight">{{ $p['sku'] }}</p>
                        <p class="text-sm text-steel-soft mt-1">{{ $p['name'] }}</p>

                        <dl class="mt-4 space-y-1.5 font-mono text-sm">
                            <div class="flex justify-between">
                                <dt class="text-haze">Размер</dt><dd class="spec-value">{{ $p['dia'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-haze">Вес</dt><dd class="spec-value">{{ $p['weight'] }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-haze">Объём</dt><dd class="spec-value">{{ $p['volume'] }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 pt-4 border-t-2 border-concrete-dark flex items-baseline justify-between gap-2">
                            <p class="font-mono text-base sm:text-lg text-steel">
                                <span class="spec-value">{{ $p['price'] }}</span>
                                <span class="text-xs text-haze">₸ /шт</span>
                            </p>
                            <x-button variant="primary" size="sm" href="#">В корзину</x-button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Button gallery --}}
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 border-t-2 border-edge">
        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4 sm:mb-6">
            ━━━━━━━ ПРИМИТИВЫ: КНОПКИ
        </p>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl uppercase mb-8 sm:mb-12">Button variants</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 sm:gap-12">
            <div>
                <p class="font-display text-sm uppercase tracking-wider text-haze mb-4">Primary (Blueprint)</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <x-button variant="primary" size="sm">Small</x-button>
                    <x-button variant="primary" size="md">Medium</x-button>
                    <x-button variant="primary" size="lg">Large</x-button>
                </div>
            </div>
            <div>
                <p class="font-display text-sm uppercase tracking-wider text-haze mb-4">Stamp (Critical)</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <x-button variant="stamp" size="sm">Удалить</x-button>
                    <x-button variant="stamp" size="md">Подтвердить заказ</x-button>
                </div>
            </div>
            <div>
                <p class="font-display text-sm uppercase tracking-wider text-haze mb-4">Outline (Secondary)</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <x-button variant="outline" size="sm">Отмена</x-button>
                    <x-button variant="outline" size="md">Запрос цены</x-button>
                </div>
            </div>
            <div>
                <p class="font-display text-sm uppercase tracking-wider text-haze mb-4">Ghost + Mono</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <x-button variant="ghost" size="md">Подробнее</x-button>
                    <x-button variant="mono" size="md">Каталог →</x-button>
                </div>
            </div>
        </div>
    </section>

    {{-- Typography sample --}}
    <section class="bg-document border-t-2 border-edge">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4 sm:mb-6">
                ━━━━━━━ ТИПОГРАФИКА
            </p>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl uppercase mb-8 sm:mb-12">Type system</h2>

            <div class="space-y-6 sm:space-y-8 max-w-2xl">
                <div>
                    <p class="text-xs text-haze font-mono uppercase mb-1">H1 — Russo One</p>
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl">Бетонные кольца</h1>
                </div>
                <div>
                    <p class="text-xs text-haze font-mono uppercase mb-1">H2 — Russo One</p>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl">Размерный ряд КС</h2>
                </div>
                <div>
                    <p class="text-xs text-haze font-mono uppercase mb-1">H3 — Russo One</p>
                    <h3 class="text-xl sm:text-2xl">Маркировка по ГОСТ 8020-90</h3>
                </div>
                <div>
                    <p class="text-xs text-haze font-mono uppercase mb-1">Body — Onest 16px</p>
                    <p class="text-base leading-relaxed">
                        Бетонное кольцо — это сборное железобетонное изделие
                        цилиндрической формы, изготовленное методом вибропрессования
                        из бетона марки М200 или выше. Применяется для устройства
                        смотровых, канализационных и дренажных колодцев.
                    </p>
                </div>
                <div>
                    <p class="text-xs text-haze font-mono uppercase mb-1">Mono — IBM Plex Mono</p>
                    <p class="font-mono text-xs sm:text-sm break-words">
                        КС10.9 · 1000 × 890 мм · 0.85 т · 0.685 м³ · М200 · ГОСТ 8020-90 · 12 500 ₸
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Palette swatches --}}
    <section class="border-t-2 border-edge">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4 sm:mb-6">
                ━━━━━━━ ПАЛИТРА
            </p>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl uppercase mb-8 sm:mb-12">Drafting Floor</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                @foreach([
                    ['bg-concrete', '#E8E4DC', 'Concrete'],
                    ['bg-concrete-dark', '#D9D4C7', 'Concrete Dark'],
                    ['bg-document', '#FAFAF7', 'Document'],
                    ['bg-steel', '#14181F', 'Steel'],
                    ['bg-steel-soft', '#2D3340', 'Steel Soft'],
                    ['bg-haze', '#6B7280', 'Haze'],
                    ['bg-blueprint-600', '#1F3A5F', 'Blueprint'],
                    ['bg-blueprint-700', '~darker', 'Blueprint Dark'],
                    ['bg-stamp-600', '#C8442B', 'Stamp'],
                    ['bg-stamp-700', '~darker', 'Stamp Dark'],
                ] as [$class, $hex, $name])
                    <div class="border-2 border-edge">
                        <div class="{{ $class }} aspect-square"></div>
                        <div class="p-2 sm:p-3 bg-document border-t-2 border-edge">
                            <p class="font-display text-[10px] sm:text-xs uppercase tracking-wider">{{ $name }}</p>
                            <p class="font-mono text-[10px] sm:text-xs text-haze mt-1">{{ $hex }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <footer class="bg-steel text-document py-8 mt-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 font-mono text-[10px] sm:text-xs uppercase tracking-wider text-haze">
            DESIGN PREVIEW · DRAFTING FLOOR · v0.1 · INTERNAL
        </div>
    </footer>

</body>
</html>
