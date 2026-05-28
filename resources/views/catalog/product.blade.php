@extends('layouts.app', [
    'meta_title' => $product->meta_title ?: $product->name.' — '.$category->name.' | ТРИ АД',
    'meta_description' => $product->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 160)
        ?: 'Купить '.$product->name.' по ГОСТ. Доставка по Казахстану.',
    'og_image' => $product->getFirstMediaUrl('real', 'og')
        ?: $product->getFirstMediaUrl('blueprint', 'og') ?: null,
    'og_type' => 'product',
    'schema_jsonld' => view('partials.schema.product', compact('product', 'category'))->render(),
])

@php
    use Illuminate\Support\Number;

    $blueprint = $product->getFirstMediaUrl('blueprint', 'card');
    $real = $product->getFirstMediaUrl('real', 'card');
    $blueprintFull = $product->getFirstMediaUrl('blueprint');
    $realFull = $product->getFirstMediaUrl('real');
    $hasBoth = $blueprint && $real;
    // Real photo is the catalog default; blueprint is the technical view.
    $defaultTab = $real ? 'real' : 'blueprint';
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[
            ['label' => 'Каталог', 'url' => url('/catalog')],
            ['label' => $category->name, 'url' => $category->url()],
            ['label' => $product->name, 'url' => $product->url($category)],
        ]" />

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

            {{-- Gallery — tabs «Чертёж / Фото» when both are present. --}}
            <div x-data="{ tab: '{{ $defaultTab }}' }">
                @if($hasBoth)
                    <div class="flex gap-2 mb-3" role="tablist">
                        <button @click="tab = 'real'"
                                :class="tab === 'real' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="px-4 py-2 text-sm font-medium rounded transition"
                                role="tab"
                                :aria-selected="tab === 'real'">
                            Фото изделия
                        </button>
                        <button @click="tab = 'blueprint'"
                                :class="tab === 'blueprint' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="px-4 py-2 text-sm font-medium rounded transition"
                                role="tab"
                                :aria-selected="tab === 'blueprint'">
                            Чертёж
                        </button>
                    </div>
                @endif

                <div class="bg-slate-50 border border-slate-200 rounded-lg overflow-hidden aspect-square">
                    @if($real)
                        <a href="{{ $realFull }}" target="_blank" rel="noopener"
                           x-show="tab === 'real'"
                           class="block w-full h-full">
                            <img src="{{ $real }}"
                                 alt="{{ $product->name }} — фото"
                                 class="w-full h-full object-contain">
                        </a>
                    @endif
                    @if($blueprint)
                        <a href="{{ $blueprintFull }}" target="_blank" rel="noopener"
                           x-show="tab === 'blueprint'"
                           @if(! $real) x-cloak @else style="display: none;" @endif
                           class="block w-full h-full">
                            <img src="{{ $blueprint }}"
                                 alt="{{ $product->name }} — чертёж"
                                 class="w-full h-full object-contain">
                        </a>
                    @endif
                    @if(! $blueprint && ! $real)
                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                            Фото скоро появится
                        </div>
                    @endif
                </div>
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
