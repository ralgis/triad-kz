@php
    $settings ??= \App\Models\Setting::current();
    $primaryPhone = $settings->phone;
    $primaryPhoneTel = $primaryPhone ? preg_replace('/[^0-9+]/', '', $primaryPhone) : null;
    $primaryWa = $settings->whatsappNumbers()[0] ?? null;
    $tg = $settings->telegramUrl();
    $scheduleLines = $settings->workingHoursLines();
@endphp
@extends('layouts.app', [
    'meta_title' => $settings->home_meta_title
        ?: 'ТРИ АД Construction — ЖБИ в Алматы',
    'meta_description' => $settings->home_meta_description
        ?: 'Производство и продажа железобетонных изделий: бетонные кольца, плиты перекрытия, ФБС, опорные подушки. Доставка по Казахстану.',
])

@section('content')

    {{-- Hero: blueprint grid bg, asymmetric layout, primary headline left,
         contact card right (stacks below on mobile). --}}
    <section class="bg-blueprint-grid border-b-2 border-edge">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-24 xl:py-32 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            <div class="lg:col-span-8">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">
                    ━━━━━━━ КАТАЛОГ ЖБИ В АЛМАТЫ
                </p>

                <h1 class="text-[2rem] leading-[1.05] sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl uppercase">
                    Железобетонные<br>изделия<br>
                    <span class="text-blueprint-600">с завода</span>
                </h1>

                <p class="mt-6 sm:mt-8 max-w-xl text-base sm:text-lg text-steel-soft leading-relaxed">
                    Производство с 2008 года. Бетонные кольца, ФБС-блоки,
                    плиты перекрытия, опорные подушки, арычные лотки.
                    <span class="font-mono spec-value">ГОСТ</span>. Серии. Сертификация.
                </p>

                <div class="mt-8 sm:mt-10">
                    <x-button variant="primary" size="lg" :href="url('/catalog')" class="w-full sm:w-auto">
                        Каталог →
                    </x-button>
                </div>
            </div>

            {{-- Contact card — Drafting Floor data-block. Phone + two
                 equal-weight call/WhatsApp CTAs + hours + Telegram/email
                 + «Получить прайс» stamp button. Each section degrades
                 gracefully when its Settings value is missing. --}}
            <aside class="lg:col-span-4 bg-document border-2 border-edge self-start">
                <div class="bg-steel text-document px-5 sm:px-6 py-3 flex items-center justify-between">
                    <p class="font-display uppercase tracking-wider text-xs sm:text-sm">Отдел продаж</p>
                    @if($scheduleLines)
                        <span class="font-mono text-[10px] sm:text-xs text-haze uppercase">
                            @if($settings->isOpenNow())Сейчас открыто@elseЗапись круглосуточно@endif
                        </span>
                    @endif
                </div>

                @if($primaryPhone)
                    <div class="px-5 sm:px-6 py-5 border-b-2 border-concrete-dark">
                        <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-1">Прямой телефон</p>
                        <p class="font-mono text-xl sm:text-2xl text-steel spec-value leading-tight mb-4">
                            {{ $primaryPhone }}
                        </p>
                        <div class="grid grid-cols-{{ $primaryWa ? '2' : '1' }} gap-2">
                            <a href="tel:{{ $primaryPhoneTel }}"
                               class="inline-flex items-center justify-center gap-2 px-3 py-3 border-2 border-edge bg-document font-display uppercase tracking-wider text-xs hover:bg-steel hover:text-document transition">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Позвонить
                            </a>
                            @if($primaryWa)
                                <a href="{{ $primaryWa['wa_url'] }}"
                                   class="inline-flex items-center justify-center gap-2 px-3 py-3 border-2 border-edge bg-document font-display uppercase tracking-wider text-xs hover:bg-steel hover:text-document transition">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                    WhatsApp
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if(! empty($scheduleLines))
                    <div class="px-5 sm:px-6 py-4 border-b-2 border-concrete-dark">
                        <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-2">Часы работы</p>
                        <ul class="space-y-1 font-mono text-sm text-steel">
                            @foreach($scheduleLines as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($tg || $settings->public_email)
                    <div class="px-5 sm:px-6 py-4 border-b-2 border-concrete-dark">
                        <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-3">Написать</p>
                        @if($tg)
                            <a href="{{ $tg }}"
                               class="inline-flex items-center gap-2 px-3 py-2 border-2 border-edge font-mono text-xs uppercase tracking-wider hover:bg-blueprint-600 hover:text-document hover:border-blueprint-600 transition">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                                Telegram
                            </a>
                        @endif
                        @if($settings->public_email)
                            <a href="mailto:{{ $settings->public_email }}"
                               class="block mt-3 font-mono text-xs text-blueprint-600 hover:text-blueprint-700 hover:underline">
                                {{ $settings->public_email }}
                            </a>
                        @endif
                    </div>
                @endif

                <a href="{{ url('/contacts') }}"
                   class="block bg-stamp-600 text-document hover:bg-stamp-700 transition px-5 sm:px-6 py-4 text-center font-display uppercase tracking-wider text-sm">
                    Запросить прайс →
                </a>
            </aside>
        </div>
    </section>

    {{-- Categories grid --}}
    @if($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4 sm:mb-6">
                ━━━━━━━ КАТЕГОРИИ КАТАЛОГА
            </p>
            <div class="flex items-end justify-between gap-4 mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl uppercase">Производство</h2>
                <a href="{{ url('/catalog') }}"
                   class="font-display uppercase tracking-wider text-xs sm:text-sm text-blueprint-600 hover:text-blueprint-700 whitespace-nowrap border-b-2 border-blueprint-600 hover:border-blueprint-700 transition pb-1">
                    Все категории →
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($categories as $category)
                    <x-category-card :category="$category" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured products --}}
    @if($products->isNotEmpty())
        <section class="bg-document border-y-2 border-edge">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4 sm:mb-6">
                    ━━━━━━━ РЕКОМЕНДУЕМЫЕ ПОЗИЦИИ
                </p>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl uppercase mb-8 sm:mb-12">Ходовые товары</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Articles --}}
    @if($articles->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4 sm:mb-6">
                ━━━━━━━ ПУБЛИКАЦИИ
            </p>
            <div class="flex items-end justify-between gap-4 mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl uppercase">Статьи</h2>
                <a href="{{ url('/blog') }}"
                   class="font-display uppercase tracking-wider text-xs sm:text-sm text-blueprint-600 hover:text-blueprint-700 whitespace-nowrap border-b-2 border-blueprint-600 hover:border-blueprint-700 transition pb-1">
                    Все статьи →
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($articles as $article)
                    <x-article-card :article="$article" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
