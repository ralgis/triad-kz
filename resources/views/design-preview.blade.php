@extends('layouts.app', [
    'meta_title' => 'Design Preview — Drafting Floor',
    'meta_description' => 'Internal preview of the new design system.',
    'noindex' => true,
])

@section('content')
    <div class="bg-concrete min-h-screen">
        {{-- Spec strip header — like a draft sheet header --}}
        <div class="border-b-2 border-edge bg-document">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 flex items-baseline justify-between">
                <div class="font-display uppercase tracking-wider text-lg">ТРИ АД CONSTRUCTION</div>
                <nav class="hidden md:flex gap-8 font-display uppercase tracking-wider text-sm">
                    <a href="#" class="hover:text-blueprint-600">Каталог</a>
                    <a href="#" class="hover:text-blueprint-600">Статьи</a>
                    <a href="#" class="hover:text-blueprint-600">ГОСТы</a>
                    <a href="#" class="hover:text-blueprint-600">Контакты</a>
                </nav>
            </div>
            <div class="border-t border-concrete-dark">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2">
                    <p class="font-mono text-xs text-haze uppercase tracking-wider">
                        ALMATY · ЖБИ · 2008 — НАСТ. · БИН 123456789012
                    </p>
                </div>
            </div>
        </div>

        {{-- Hero — asymmetric, blueprint grid bg --}}
        <section class="bg-blueprint-grid border-b-2 border-edge">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 lg:py-32 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-8">
                    <p class="font-mono text-xs text-haze uppercase tracking-wider mb-4">
                        ━━━━━━━ КАТАЛОГ ЖБИ
                    </p>
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-display uppercase leading-none">
                        Железобетонные<br>изделия<br>
                        <span class="text-blueprint-600">в Алматы</span>
                    </h1>
                    <p class="mt-8 max-w-xl text-lg text-steel-soft leading-relaxed">
                        Производство с 2008 года. Бетонные кольца, ФБС-блоки,
                        плиты перекрытия, опорные подушки, арычные лотки.
                        ГОСТ. Серии. Сертификация.
                    </p>
                    <div class="mt-10 flex flex-wrap gap-4">
                        <x-button variant="primary" size="lg" href="#">Каталог →</x-button>
                        <x-button variant="outline" size="lg" href="#">Запрос цены</x-button>
                    </div>
                </div>

                {{-- Info card — like a technical spec block --}}
                <aside class="lg:col-span-4 bg-document border-2 border-edge p-6 self-start">
                    <p class="font-display uppercase tracking-wider text-xs text-haze mb-4">
                        Тех. сводка
                    </p>
                    <dl class="space-y-3 font-mono text-sm">
                        <div class="flex justify-between border-b border-concrete-dark pb-2">
                            <dt class="text-haze">SKU</dt><dd class="text-steel">38</dd>
                        </div>
                        <div class="flex justify-between border-b border-concrete-dark pb-2">
                            <dt class="text-haze">Категорий</dt><dd class="text-steel">9</dd>
                        </div>
                        <div class="flex justify-between border-b border-concrete-dark pb-2">
                            <dt class="text-haze">ГОСТов</dt><dd class="text-steel">7</dd>
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

        {{-- Product card example — to check the «catalog index card» direction --}}
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
            <p class="font-mono text-xs text-haze uppercase tracking-wider mb-6">
                ━━━━━━━ ПРИМЕР: КАРТОЧКА ТОВАРА
            </p>
            <h2 class="text-3xl font-display uppercase mb-12">Бетонные кольца</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    ['sku' => 'КС10.9', 'name' => 'Бетонное кольцо стеновое', 'dia' => '1000 × 890 мм', 'weight' => '0.85 т', 'volume' => '0.685 м³', 'price' => '12 500'],
                    ['sku' => 'КС15.9', 'name' => 'Бетонное кольцо стеновое', 'dia' => '1500 × 890 мм', 'weight' => '1.45 т', 'volume' => '1.156 м³', 'price' => '21 800'],
                    ['sku' => 'КС20.9', 'name' => 'Бетонное кольцо стеновое', 'dia' => '2000 × 890 мм', 'weight' => '2.15 т', 'volume' => '1.720 м³', 'price' => '33 400'],
                ] as $p)
                    <article class="bg-document border-2 border-edge group hover:translate-y-[-2px] transition">
                        <div class="aspect-square bg-concrete-dark border-b-2 border-edge flex items-center justify-center text-haze relative">
                            <span class="font-mono text-xs">[фото товара]</span>
                            <div class="absolute top-3 right-3 bg-stamp-600 text-document px-3 py-1 font-mono text-xs uppercase tracking-wider">
                                ГОСТ 8020-90
                            </div>
                        </div>
                        <div class="p-5">
                            <p class="font-display text-2xl uppercase tracking-tight">{{ $p['sku'] }}</p>
                            <p class="text-sm text-steel-soft mt-1">{{ $p['name'] }}</p>

                            <dl class="mt-4 space-y-1.5 font-mono text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-haze">Размер</dt><dd class="spec-value">{{ $p['dia'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-haze">Вес</dt><dd class="spec-value">{{ $p['weight'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-haze">Объём бетона</dt><dd class="spec-value">{{ $p['volume'] }}</dd>
                                </div>
                            </dl>

                            <div class="mt-5 pt-4 border-t-2 border-concrete-dark flex items-baseline justify-between">
                                <p class="font-mono text-lg text-steel">
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
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 border-t-2 border-edge">
            <p class="font-mono text-xs text-haze uppercase tracking-wider mb-6">
                ━━━━━━━ ПРИМИТИВЫ: КНОПКИ
            </p>
            <h2 class="text-3xl font-display uppercase mb-12">Button variants</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
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
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
                <p class="font-mono text-xs text-haze uppercase tracking-wider mb-6">
                    ━━━━━━━ ТИПОГРАФИКА
                </p>
                <h2 class="text-3xl font-display uppercase mb-12">Type system</h2>

                <div class="space-y-8 max-w-2xl">
                    <div>
                        <p class="text-xs text-haze font-mono uppercase mb-1">H1 — Russo One 60-72px</p>
                        <h1 class="text-6xl">Бетонные кольца</h1>
                    </div>
                    <div>
                        <p class="text-xs text-haze font-mono uppercase mb-1">H2 — Russo One 36-48px</p>
                        <h2 class="text-4xl">Размерный ряд КС</h2>
                    </div>
                    <div>
                        <p class="text-xs text-haze font-mono uppercase mb-1">H3 — Russo One 24-30px</p>
                        <h3 class="text-2xl">Маркировка по ГОСТ 8020-90</h3>
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
                        <p class="text-xs text-haze font-mono uppercase mb-1">Mono — IBM Plex Mono 14px (для всех чисел)</p>
                        <p class="font-mono text-sm">
                            КС10.9 · 1000 × 890 мм · 0.85 т · 0.685 м³ · М200 · ГОСТ 8020-90 · 12 500 ₸
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Palette swatches --}}
        <section class="border-t-2 border-edge">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
                <p class="font-mono text-xs text-haze uppercase tracking-wider mb-6">
                    ━━━━━━━ ПАЛИТРА
                </p>
                <h2 class="text-3xl font-display uppercase mb-12">Drafting Floor</h2>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
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
                            <div class="p-3 bg-document border-t-2 border-edge">
                                <p class="font-display text-xs uppercase tracking-wider">{{ $name }}</p>
                                <p class="font-mono text-xs text-haze mt-1">{{ $hex }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
