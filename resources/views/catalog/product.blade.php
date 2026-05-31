@extends('layouts.app', [
    'meta_title' => $product->meta_title ?: $product->name.' — '.$category->name.' | ТРИ АД',
    'meta_description' => $product->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 160)
        ?: 'Купить '.$product->name.' по ГОСТ. Доставка по Казахстану.',
    'og_image' => $product->getFirstMediaUrl('images', 'og') ?: null,
    'og_type' => 'product',
    'schema_jsonld' => view('partials.schema.product', compact('product', 'category'))->render(),
])

@php
    use Illuminate\Support\Number;

    $imageTitle = $product->imageTitle();
    $images = $product->getMedia('images')->values()->map(fn ($m, $i) => [
        'card' => $m->getUrl('card'),
        'full' => $m->getUrl(),
        'alt' => $product->imageAlt($i),
        'title' => $imageTitle,
    ])->all();

    $specs = $product->specRows();
    $freshness = $product->priceFreshnessLabel();
    $firstGost = $product->gosts->first();
@endphp

@section('content')
    <article class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[
            ['label' => 'Каталог', 'url' => url('/catalog')],
            ['label' => $category->name, 'url' => $category->url()],
            ['label' => $product->name, 'url' => $product->url($category)],
        ]" />

        <p class="mt-6 sm:mt-8 font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">
            ━━━━━━━ КАТАЛОГ · {{ mb_strtoupper($category->name) }}
            @if($product->sku)
                <span class="ml-2">· АРТИКУЛ <span class="spec-value text-steel">{{ $product->sku }}</span></span>
            @endif
        </p>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">

            {{-- Gallery — Swiper carousel with thumbnail strip. Falls back
                 to a hard-edged "no photo" placeholder when empty. --}}
            <div>
                @if(count($images) === 0)
                    <div class="bg-concrete-dark border-2 border-edge aspect-square flex items-center justify-center font-mono text-xs text-haze uppercase tracking-wider">
                        ⊘ Фото скоро появится
                    </div>
                @else
                    <div x-data="productGallery({{ Js::from($images) }})"
                         x-init="init()"
                         @keydown.escape.window="closeLightbox"
                         @keydown.arrow-left.window="lightboxOpen && prevLightbox()"
                         @keydown.arrow-right.window="lightboxOpen && nextLightbox()">

                        {{-- Main swiper. ГОСТ-stamp ribbon mirrors the
                             catalog card so the page reads consistently
                             with the grid the user came from. --}}
                        <div class="relative bg-concrete-dark border-2 border-edge aspect-square overflow-hidden">
                            @if($firstGost)
                                <div class="absolute top-3 right-3 z-10 bg-stamp-600 text-document px-3 py-1 font-mono text-[10px] sm:text-xs uppercase tracking-wider">
                                    {{ $firstGost->fullLabel() }}
                                </div>
                            @endif

                            <div class="swiper product-main-swiper h-full" x-ref="main">
                                <div class="swiper-wrapper">
                                    @foreach($images as $i => $img)
                                        <div class="swiper-slide">
                                            <button type="button"
                                                    @click="openLightbox({{ $i }})"
                                                    class="block w-full h-full cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-blueprint-600 focus-visible:ring-inset">
                                                <img src="{{ $img['card'] }}"
                                                     alt="{{ $img['alt'] }}"
                                                     title="{{ $img['title'] }}"
                                                     class="w-full h-full object-contain"
                                                     @if($i === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                @if(count($images) > 1)
                                    <div class="swiper-button-prev !text-steel"></div>
                                    <div class="swiper-button-next !text-steel"></div>
                                @endif
                            </div>
                        </div>

                        {{-- Thumbnail strip — only when >1 image. Hard
                             borders, no rounding; Swiper toggles
                             `swiper-slide-thumb-active` for the active
                             one (styled in app.css). --}}
                        @if(count($images) > 1)
                            <div class="swiper product-thumbs-swiper mt-3" x-ref="thumbs">
                                <div class="swiper-wrapper">
                                    @foreach($images as $img)
                                        <div class="swiper-slide !w-20 !h-20 cursor-pointer border-2 border-edge overflow-hidden bg-concrete-dark">
                                            <img src="{{ $img['card'] }}"
                                                 alt=""
                                                 class="w-full h-full object-contain"
                                                 loading="lazy">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Lightbox — fullscreen viewer on near-black
                             backdrop. Light controls for legibility. --}}
                        <div x-show="lightboxOpen"
                             x-transition.opacity
                             @click.self="closeLightbox"
                             class="fixed inset-0 z-50 bg-edge/95 flex items-center justify-center p-4"
                             style="display: none;"
                             role="dialog"
                             aria-modal="true">

                            <button type="button"
                                    @click="closeLightbox"
                                    class="absolute top-4 right-4 size-10 flex items-center justify-center text-document/80 hover:text-document bg-document/10 hover:bg-document/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-document border-2 border-document/30"
                                    aria-label="Закрыть">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            @if(count($images) > 1)
                                <button type="button"
                                        @click.stop="prevLightbox"
                                        class="absolute left-4 top-1/2 -translate-y-1/2 size-12 flex items-center justify-center text-document/80 hover:text-document bg-document/10 hover:bg-document/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-document border-2 border-document/30"
                                        aria-label="Предыдущая">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <button type="button"
                                        @click.stop="nextLightbox"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 size-12 flex items-center justify-center text-document/80 hover:text-document bg-document/10 hover:bg-document/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-document border-2 border-document/30"
                                        aria-label="Следующая">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            @endif

                            <img :src="images[lightboxIndex].full"
                                 :alt="images[lightboxIndex].alt"
                                 :title="images[lightboxIndex].title"
                                 class="max-w-[95vw] max-h-[90vh] object-contain"
                                 @click.stop>

                            @if(count($images) > 1)
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 font-mono text-xs text-document/80 bg-document/10 px-3 py-1 border-2 border-document/30 uppercase tracking-wider">
                                    <span x-text="lightboxIndex + 1"></span> / {{ count($images) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Specs + price + CTAs. --}}
            <div>
                {{-- ГОСТ / Серия badges from the reference table. Kind
                     colors mirror gosts/index.blade.php so the visual
                     mapping between catalog and standards is consistent. --}}
                @if($product->gosts->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->gosts as $g)
                            <a href="{{ $g->url() }}"
                               @class([
                                   'font-mono text-[10px] uppercase tracking-wider px-2 py-1 border-2 transition hover:translate-y-[-1px]',
                                   'text-blueprint-700 border-blueprint-600 bg-blueprint-50' => $g->kind === \App\Models\Gost::KIND_GOST,
                                   'text-steel border-edge bg-document' => $g->kind === \App\Models\Gost::KIND_SERIYA,
                                   'text-stamp-700 border-stamp-600 bg-stamp-50' => $g->kind === \App\Models\Gost::KIND_TOO,
                               ])>
                                {{ $g->fullLabel() }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- SKU as load-bearing display element (mirrors catalog
                     card). Full name as subtitle. --}}
                @if($product->sku)
                    <p class="mt-4 font-display uppercase tracking-tight text-3xl sm:text-4xl text-steel">{{ $product->sku }}</p>
                    <h1 class="mt-2 text-base sm:text-lg text-steel-soft leading-snug">{{ $product->name }}</h1>
                @else
                    <h1 class="mt-4 font-display uppercase tracking-tight text-3xl sm:text-4xl text-steel">{{ $product->name }}</h1>
                @endif

                @if(! empty($specs))
                    <div class="mt-6 border-2 border-edge bg-document">
                        <p class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm">
                            Технические характеристики
                        </p>
                        <dl class="divide-y-2 divide-concrete-dark">
                            @foreach($specs as $row)
                                <div class="grid grid-cols-2 gap-4 px-4 py-2.5">
                                    <dt class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider self-center">{{ $row['label'] }}</dt>
                                    <dd class="font-mono text-sm spec-value text-steel">
                                        {{ $row['value'] }}@if($row['unit']) {{ $row['unit'] }}@endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div class="mt-6 p-5 sm:p-6 bg-concrete border-2 border-edge">
                    @if($product->price_visible && $product->price !== null)
                        <p class="font-mono text-[10px] text-haze uppercase tracking-wider">━━ Цена</p>
                        <p class="mt-2 font-display text-3xl sm:text-4xl uppercase tracking-tight text-steel leading-none">
                            <span class="spec-value">{{ Number::format((float) $product->price, locale: 'ru') }}</span>
                            <span class="font-mono text-base sm:text-lg text-steel-soft normal-case tracking-normal">₸@if($product->price_unit) /{{ $product->price_unit }}@endif</span>
                        </p>

                        @if($freshness)
                            <p class="mt-2 font-mono text-[10px] text-haze uppercase tracking-wider flex items-center gap-1.5">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12l4.243 4.243L19 6.515"/></svg>
                                Цена актуальна · {{ $freshness }}
                            </p>
                        @endif

                        @if(! $product->in_stock)
                            <p class="mt-2 font-mono text-[10px] text-stamp-700 uppercase tracking-wider">⊘ Под заказ</p>
                        @endif

                        <form action="{{ route('cart.add') }}" method="POST" class="mt-5 flex flex-col sm:flex-row gap-3 items-stretch">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="flex items-center gap-2 bg-document border-2 border-edge px-3 py-2">
                                <label for="qty" class="font-mono text-[10px] text-haze uppercase tracking-wider">Кол-во</label>
                                <input type="number" name="qty" id="qty" value="1" min="1" max="999"
                                       class="w-16 font-mono spec-value text-steel bg-transparent focus:outline-none">
                                <span class="font-mono text-[10px] text-haze uppercase tracking-wider">{{ $product->unit_for_order ?: 'шт' }}</span>
                            </div>
                            <x-button type="submit" variant="primary" size="lg" class="flex-1">
                                В корзину →
                            </x-button>
                        </form>
                    @else
                        <p class="font-mono text-[10px] text-haze uppercase tracking-wider">━━ Цена</p>
                        <p class="mt-2 font-display text-xl sm:text-2xl uppercase tracking-tight text-steel">
                            По запросу
                        </p>
                        <p class="mt-2 text-sm text-steel-soft leading-relaxed">
                            Стоимость рассчитывается индивидуально под объём и условия доставки.
                        </p>
                        <x-button :href="url('/contacts?product='.$product->id)" variant="primary" size="lg" class="mt-5 w-full">
                            Запросить расчёт →
                        </x-button>
                    @endif
                </div>

                @if(session('cart.added') === $product->name)
                    <p class="mt-3 px-3 py-2 border-2 border-blueprint-600 bg-blueprint-50 font-mono text-[10px] uppercase tracking-wider text-blueprint-700">
                        ✓ Товар добавлен в корзину
                    </p>
                @endif
            </div>
        </div>

        @if($product->description)
            <section class="mt-12 sm:mt-16">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">━━━━━━━ ОПИСАНИЕ</p>
                <div class="prose prose-slate max-w-3xl
                            prose-headings:font-display prose-headings:uppercase prose-headings:tracking-tight prose-headings:text-steel
                            prose-h2:text-2xl prose-h2:sm:text-3xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:pb-2 prose-h2:border-b-2 prose-h2:border-concrete-dark
                            prose-h3:text-xl prose-h3:sm:text-2xl prose-h3:mt-8 prose-h3:mb-3
                            prose-p:text-steel prose-p:leading-relaxed
                            prose-a:text-blueprint-600 prose-a:no-underline hover:prose-a:underline
                            prose-strong:text-steel prose-strong:font-bold
                            prose-li:text-steel
                            prose-blockquote:border-l-4 prose-blockquote:border-blueprint-600 prose-blockquote:bg-document prose-blockquote:py-1 prose-blockquote:px-4 prose-blockquote:not-italic prose-blockquote:text-steel-soft
                            prose-table:border-2 prose-table:border-edge
                            prose-th:bg-document prose-th:font-display prose-th:uppercase prose-th:tracking-wider prose-th:text-xs prose-th:text-haze
                            prose-td:font-mono prose-td:text-sm
                            prose-code:font-mono prose-code:text-blueprint-700">
                    {!! $product->description !!}
                </div>
            </section>
        @endif

        {{-- Related blocks — admin-curated complementary first (cross-
             category, higher intent), then auto-derived siblings from
             the same category. Each block hides itself when empty so
             no stranded section headers. --}}
        @if($complementary->isNotEmpty())
            <section class="mt-14 sm:mt-16 pt-8 border-t-2 border-edge">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">━━━━━━━ С ЭТИМ ТОВАРОМ ПОКУПАЮТ</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    @foreach($complementary as $cp)
                        <x-product-card :product="$cp" />
                    @endforeach
                </div>
            </section>
        @endif

        @if($alsoInCategory->isNotEmpty())
            <section class="mt-14 sm:mt-16 pt-8 border-t-2 border-edge">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">━━━━━━━ ТАКЖЕ В КАТЕГОРИИ «{{ mb_strtoupper($category->name) }}»</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    @foreach($alsoInCategory as $sibling)
                        <x-product-card :product="$sibling" />
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
