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

            <aside class="lg:col-span-4 bg-document border-2 border-edge p-5 sm:p-6 self-start">
                <p class="font-display uppercase tracking-wider text-xs text-haze mb-4">
                    Тех. сводка
                </p>
                <dl class="space-y-3 font-mono text-sm">
                    <div class="flex justify-between border-b border-concrete-dark pb-2">
                        <dt class="text-haze">SKU</dt><dd class="spec-value">38</dd>
                    </div>
                    <div class="flex justify-between border-b border-concrete-dark pb-2">
                        <dt class="text-haze">Категорий</dt><dd class="spec-value">9</dd>
                    </div>
                    <div class="flex justify-between border-b border-concrete-dark pb-2">
                        <dt class="text-haze">ГОСТов</dt><dd class="spec-value">7</dd>
                    </div>
                    <div class="flex justify-between border-b border-concrete-dark pb-2">
                        <dt class="text-haze">Доставка</dt><dd class="text-steel">по РК</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-haze">Самовывоз</dt><dd class="text-steel">Алматы</dd>
                    </div>
                </dl>
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
