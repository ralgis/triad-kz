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

    // Unified 'images' collection — admin drag-orders, first one is
    // primary (used in product-card grids + OG meta). Map to a simple
    // [card, full] tuple per slide.
    $images = $product->getMedia('images')->map(fn ($m) => [
        'card' => $m->getUrl('card'),
        'full' => $m->getUrl(),
        'alt' => $product->name,
    ])->all();
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[
            ['label' => 'Каталог', 'url' => url('/catalog')],
            ['label' => $category->name, 'url' => $category->url()],
            ['label' => $product->name, 'url' => $product->url($category)],
        ]" />

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

            {{-- Gallery — Swiper carousel with thumbnail strip. Falls
                 back to a "no photo yet" placeholder when empty. --}}
            <div>
                @if(count($images) === 0)
                    <div class="bg-slate-50 border border-slate-200 rounded-lg overflow-hidden aspect-square flex items-center justify-center text-slate-400">
                        Фото скоро появится
                    </div>
                @else
                    <div x-data="productGallery({{ Js::from($images) }})" x-init="init()">
                        {{-- Main image swiper --}}
                        <div class="bg-slate-50 border border-slate-200 rounded-lg overflow-hidden aspect-square">
                            <div class="swiper product-main-swiper h-full" x-ref="main">
                                <div class="swiper-wrapper">
                                    @foreach($images as $img)
                                        <div class="swiper-slide">
                                            <a href="{{ $img['full'] }}" target="_blank" rel="noopener"
                                               class="block w-full h-full">
                                                <img src="{{ $img['card'] }}"
                                                     alt="{{ $img['alt'] }}"
                                                     class="w-full h-full object-contain"
                                                     loading="lazy">
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                @if(count($images) > 1)
                                    <div class="swiper-button-prev !text-slate-700"></div>
                                    <div class="swiper-button-next !text-slate-700"></div>
                                @endif
                            </div>
                        </div>

                        {{-- Thumbnail strip — only when >1 image --}}
                        @if(count($images) > 1)
                            <div class="swiper product-thumbs-swiper mt-3" x-ref="thumbs">
                                <div class="swiper-wrapper">
                                    @foreach($images as $img)
                                        <div class="swiper-slide !w-20 !h-20 cursor-pointer rounded border-2 border-transparent overflow-hidden">
                                            <img src="{{ $img['card'] }}"
                                                 alt=""
                                                 class="w-full h-full object-cover"
                                                 loading="lazy">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Specs + price + CTAs. --}}
            <div>
                @if($product->gost)
                    <p class="text-sm text-slate-500 uppercase tracking-wider mb-2">{{ $product->gost }}</p>
                @endif

                <h1 class="text-2xl sm:text-3xl font-semibold text-slate-900">{{ $product->name }}</h1>

                @if($product->sku)
                    <p class="mt-2 text-sm text-slate-500">Артикул: {{ $product->sku }}</p>
                @endif

                @php
                    $dims = $product->dimensions ?? [];
                    $weight = $product->weight_kg;
                    $hasSpecs = ! empty($dims) || $weight;
                @endphp

                @if($hasSpecs)
                    <dl class="mt-6 grid grid-cols-2 gap-x-4 gap-y-2 text-sm border-t border-slate-200 pt-4">
                        @foreach($dims as $key => $value)
                            @if($value !== null && $value !== '')
                                <dt class="text-slate-500">{{ \App\Support\DimensionLabels::label($key) }}</dt>
                                <dd class="text-slate-900 font-medium">{{ $value }} мм</dd>
                            @endif
                        @endforeach
                        @if($weight)
                            <dt class="text-slate-500">Вес</dt>
                            <dd class="text-slate-900 font-medium">{{ $weight }} кг</dd>
                        @endif
                    </dl>
                @endif

                <div class="mt-6 p-6 bg-slate-50 border border-slate-200 rounded-lg">
                    @if($product->price_visible && $product->price !== null)
                        <p class="text-3xl font-semibold text-slate-900">
                            {{ Number::format((float) $product->price, locale: 'ru') }} ₸
                            @if($product->price_unit)
                                <span class="text-base font-normal text-slate-500">/ {{ $product->price_unit }}</span>
                            @endif
                        </p>

                        @if(! $product->in_stock)
                            <p class="mt-2 text-sm font-medium text-amber-700">Под заказ</p>
                        @endif

                        <form action="{{ route('cart.add') }}" method="POST" class="mt-5 flex flex-col sm:flex-row gap-3">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="flex items-center gap-2">
                                <label for="qty" class="text-sm text-slate-700">Кол-во</label>
                                <input type="number" name="qty" id="qty" value="1" min="1" max="999"
                                       class="w-20 rounded border-slate-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                                <span class="text-sm text-slate-500">{{ $product->unit_for_order ?: 'шт' }}</span>
                            </div>
                            <x-button type="submit" variant="primary" size="lg" class="flex-1">
                                В корзину
                            </x-button>
                        </form>
                    @else
                        <p class="text-lg text-slate-700">Цена уточняется индивидуально под заказ.</p>
                        <x-button :href="url('/contacts?product='.$product->id)" variant="primary" size="lg" class="mt-4">
                            Запросить цену
                        </x-button>
                    @endif
                </div>

                @if(session('cart.added') === $product->name)
                    <p class="mt-3 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-3 py-2">
                        Товар добавлен в корзину.
                    </p>
                @endif
            </div>
        </div>

        @if($product->description)
            <div class="mt-12 max-w-3xl">
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Описание</h2>
                <div class="prose prose-slate max-w-none">
                    {!! $product->description !!}
                </div>
            </div>
        @endif
    </div>
@endsection
