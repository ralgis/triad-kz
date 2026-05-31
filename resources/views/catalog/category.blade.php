@extends('layouts.app', [
    'meta_title' => $category->meta_title ?: $category->name.' — каталог ТРИ АД',
    'meta_description' => $category->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags($category->description ?? ''), 160)
        ?: 'Купить '.mb_strtolower($category->name).' по ГОСТ в Алматы. Производство, доставка по Казахстану.',
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[
            ['label' => 'Каталог', 'url' => url('/catalog')],
            ['label' => $category->name, 'url' => $category->url()],
        ]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ КАТЕГОРИЯ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">{{ $category->name }}</h1>
            @if($category->description)
                <div class="prose prose-slate mt-4 max-w-3xl
                            prose-headings:font-display prose-headings:uppercase
                            prose-p:text-steel-soft prose-strong:text-steel
                            prose-a:text-blueprint-600 hover:prose-a:underline
                            prose-li:text-steel-soft">
                    {!! $category->description !!}
                </div>
            @endif
        </header>

        @if($children->isNotEmpty())
            <section class="mt-10">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                    ━━━━━━━ ПОДКАТЕГОРИИ
                </p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    @foreach($children as $child)
                        <x-category-card :category="$child" />
                    @endforeach
                </div>
            </section>
        @endif

        @php
            $hasFilters = ! empty($filterMeta['numeric']) || ! empty($filterMeta['grades']);
        @endphp

        @if($hasFilters)
            <div class="mt-10 grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6 lg:gap-8">

                {{-- Filter sidebar — CAD-properties-panel aesthetic --}}
                <aside x-data="{ open: false }" class="lg:sticky lg:top-24 lg:self-start">
                    <button type="button"
                            @click="open = !open"
                            class="lg:hidden w-full flex items-center justify-between px-4 py-3 bg-document border-2 border-edge font-display uppercase tracking-wider text-sm text-steel">
                        <span>Фильтры@if(count($activeFilters))<span class="ml-2 font-mono text-xs bg-stamp-600 text-document px-2 py-0.5">{{ count($activeFilters) }}</span>@endif</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="transition-transform" :class="open && 'rotate-180'">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>

                    <form method="GET" action="{{ $category->url() }}"
                          x-show="open || window.matchMedia('(min-width: 1024px)').matches"
                          x-cloak
                          class="mt-3 lg:mt-0 bg-document border-2 border-edge">
                        <div class="bg-steel text-document px-4 py-3 flex items-center justify-between">
                            <p class="font-display uppercase tracking-wider text-xs sm:text-sm">Фильтры</p>
                            @if(count($activeFilters))
                                <a href="{{ $category->url() }}" class="font-mono text-[10px] text-haze uppercase tracking-wider hover:text-document transition">сбросить</a>
                            @endif
                        </div>

                        <div class="px-4 py-4 space-y-5">
                            <div>
                                <label for="catalog-search" class="block font-mono text-[10px] text-haze uppercase tracking-wider mb-1.5">Поиск по названию / SKU</label>
                                <input type="search"
                                       id="catalog-search"
                                       name="q"
                                       value="{{ $searchQuery }}"
                                       placeholder="например: КС10 или ФБС"
                                       class="block w-full bg-concrete border-2 border-edge px-3 py-2 text-sm text-steel placeholder:text-haze focus:border-blueprint-600 focus:outline-none transition">
                            </div>

                            <div>
                                <label for="catalog-sort" class="block font-mono text-[10px] text-haze uppercase tracking-wider mb-1.5">Сортировка</label>
                                <select id="catalog-sort"
                                        name="sort"
                                        class="block w-full bg-concrete border-2 border-edge px-3 py-2 text-sm text-steel focus:border-blueprint-600 focus:outline-none transition">
                                    @foreach($sortOptions as $key => $label)
                                        <option value="{{ $key }}" @selected($activeSort === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @foreach($filterMeta['numeric'] as $column => $info)
                                <fieldset class="space-y-1.5">
                                    <legend class="font-mono text-[10px] text-haze uppercase tracking-wider">
                                        {{ $info['label'] }}, {{ $info['unit'] }}
                                        <span class="text-haze/60">({{ $info['min'] }}–{{ $info['max'] }})</span>
                                    </legend>
                                    <div class="flex gap-2">
                                        <input type="number"
                                               name="{{ $column }}_min"
                                               value="{{ request()->query($column.'_min') }}"
                                               placeholder="от {{ $info['min'] }}"
                                               step="{{ $info['step'] }}"
                                               min="0"
                                               class="w-full bg-concrete border-2 border-edge font-mono text-xs px-2 py-1.5 text-steel placeholder:text-haze focus:border-blueprint-600 focus:outline-none">
                                        <input type="number"
                                               name="{{ $column }}_max"
                                               value="{{ request()->query($column.'_max') }}"
                                               placeholder="до {{ $info['max'] }}"
                                               step="{{ $info['step'] }}"
                                               min="0"
                                               class="w-full bg-concrete border-2 border-edge font-mono text-xs px-2 py-1.5 text-steel placeholder:text-haze focus:border-blueprint-600 focus:outline-none">
                                    </div>
                                </fieldset>
                            @endforeach

                            @if(! empty($filterMeta['grades']))
                                <fieldset class="space-y-2">
                                    <legend class="font-mono text-[10px] text-haze uppercase tracking-wider">Марка бетона</legend>
                                    @php($selectedGrades = (array) request()->query('grades', []))
                                    @foreach($filterMeta['grades'] as $grade)
                                        <label class="flex items-center gap-2 font-mono text-sm text-steel cursor-pointer hover:text-blueprint-600 transition">
                                            <input type="checkbox"
                                                   name="grades[]"
                                                   value="{{ $grade }}"
                                                   @checked(in_array($grade, $selectedGrades, true))
                                                   class="size-4 border-2 border-edge bg-document text-blueprint-600 focus:ring-blueprint-600 focus:ring-offset-0">
                                            <span class="spec-value">{{ $grade }}</span>
                                        </label>
                                    @endforeach
                                </fieldset>
                            @endif

                            <button type="submit"
                                    class="w-full px-3 py-3 bg-blueprint-600 hover:bg-blueprint-700 text-document font-display uppercase tracking-wider text-xs transition border-2 border-blueprint-600">
                                Применить →
                            </button>
                        </div>
                    </form>
                </aside>

                {{-- Products grid + active filter chips --}}
                <div>
                    @if(count($activeFilters))
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <span class="font-mono text-[10px] text-haze uppercase tracking-wider">━━ Активные:</span>
                            @foreach($activeFilters as $chip)
                                <span class="inline-flex items-center px-2 py-1 bg-blueprint-50 border-2 border-blueprint-600 font-mono text-[10px] text-blueprint-700 uppercase tracking-wider">
                                    {{ $chip['label'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($products->isEmpty())
                        <div class="p-6 border-2 border-edge bg-document">
                            <p class="font-mono text-sm text-haze uppercase tracking-wider">
                                @if(count($activeFilters))
                                    Под выбранные фильтры ничего не нашлось.
                                    <a href="{{ $category->url() }}" class="text-blueprint-600 hover:underline normal-case">Сбросить</a>
                                @else
                                    В этой категории пока нет товаров.
                                @endif
                            </p>
                        </div>
                    @else
                        @if($children->isNotEmpty())
                            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">━━━━━━━ ТОВАРЫ</p>
                        @endif
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                            @foreach($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            @if($products->isEmpty())
                <div class="mt-10 p-6 border-2 border-edge bg-document">
                    <p class="font-mono text-sm text-haze uppercase tracking-wider">В этой категории пока нет товаров.</p>
                </div>
            @else
                @if($children->isNotEmpty())
                    <p class="mt-10 font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">━━━━━━━ ТОВАРЫ</p>
                @endif
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
