@extends('layouts.app', [
    'meta_title' => $category->meta_title ?: $category->name.' — каталог ТРИ АД',
    'meta_description' => $category->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags($category->description ?? ''), 160)
        ?: 'Купить '.mb_strtolower($category->name).' по ГОСТ в Алматы. Производство, доставка по Казахстану.',
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[
            ['label' => 'Каталог', 'url' => url('/catalog')],
            ['label' => $category->name, 'url' => $category->url()],
        ]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">{{ $category->name }}</h1>

        @if($category->description)
            <div class="prose prose-slate mt-4 max-w-3xl">
                {!! $category->description !!}
            </div>
        @endif

        {{-- Subcategories first, then products. Most ЖБИ catalogs are flat
             so usually this block is empty; kept generic for future depth. --}}
        @if($children->isNotEmpty())
            <h2 class="mt-10 text-xl font-semibold text-slate-900">Подкатегории</h2>
            <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($children as $child)
                    <x-category-card :category="$child" />
                @endforeach
            </div>
        @endif

        @php
            $hasFilters = ! empty($filterMeta['numeric']) || ! empty($filterMeta['grades']);
        @endphp

        @if($hasFilters)
            <div class="mt-10 grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 lg:gap-8">

                {{-- Filter sidebar — sticky on desktop, collapsible on mobile via Alpine. --}}
                <aside x-data="{ open: false }" class="lg:sticky lg:top-24 lg:self-start">
                    <button type="button"
                            @click="open = !open"
                            class="lg:hidden w-full flex items-center justify-between px-4 py-3 bg-white border border-slate-200 rounded-lg text-slate-800 font-medium">
                        <span>Фильтры@if(count($activeFilters)) <span class="ml-2 text-xs bg-brand-600 text-white px-2 py-0.5 rounded-full">{{ count($activeFilters) }}</span>@endif</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <form method="GET" action="{{ $category->url() }}"
                          x-show="open || window.matchMedia('(min-width: 1024px)').matches"
                          x-cloak
                          class="mt-3 lg:mt-0 p-4 lg:p-5 bg-white border border-slate-200 rounded-lg space-y-5">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wide">Фильтры</h2>
                            @if(count($activeFilters))
                                <p class="mt-1 text-xs text-slate-500">
                                    Активно: {{ count($activeFilters) }} —
                                    <a href="{{ $category->url() }}" class="text-brand-600 hover:underline">сбросить</a>
                                </p>
                            @endif
                        </div>

                        @foreach($filterMeta['numeric'] as $column => $info)
                            <fieldset class="space-y-1">
                                <legend class="text-xs font-medium text-slate-700">
                                    {{ $info['label'] }}, {{ $info['unit'] }}
                                    <span class="text-slate-400 font-normal">({{ $info['min'] }}–{{ $info['max'] }})</span>
                                </legend>
                                <div class="flex gap-2">
                                    <input type="number"
                                           name="{{ $column }}_min"
                                           value="{{ request()->query($column.'_min') }}"
                                           placeholder="от {{ $info['min'] }}"
                                           step="{{ $info['step'] }}"
                                           min="0"
                                           class="w-full rounded border-slate-300 text-sm px-2 py-1.5 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                                    <input type="number"
                                           name="{{ $column }}_max"
                                           value="{{ request()->query($column.'_max') }}"
                                           placeholder="до {{ $info['max'] }}"
                                           step="{{ $info['step'] }}"
                                           min="0"
                                           class="w-full rounded border-slate-300 text-sm px-2 py-1.5 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                                </div>
                            </fieldset>
                        @endforeach

                        @if(! empty($filterMeta['grades']))
                            <fieldset class="space-y-1.5">
                                <legend class="text-xs font-medium text-slate-700">Марка бетона</legend>
                                @php($selectedGrades = (array) request()->query('grades', []))
                                @foreach($filterMeta['grades'] as $grade)
                                    <label class="flex items-center gap-2 text-sm text-slate-800 cursor-pointer">
                                        <input type="checkbox"
                                               name="grades[]"
                                               value="{{ $grade }}"
                                               @checked(in_array($grade, $selectedGrades, true))
                                               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <span>{{ $grade }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @endif

                        <button type="submit"
                                class="w-full px-3 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded">
                            Применить
                        </button>
                    </form>
                </aside>

                {{-- Products grid + active-filter chips above it. --}}
                <div>
                    @if(count($activeFilters))
                        <div class="mb-4 flex flex-wrap gap-2 text-sm">
                            <span class="text-slate-500">Активные фильтры:</span>
                            @foreach($activeFilters as $chip)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-brand-50 text-brand-700 rounded text-xs">
                                    {{ $chip['label'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($products->isEmpty())
                        <p class="text-slate-500 italic">
                            @if(count($activeFilters))
                                Под выбранные фильтры ничего не нашлось.
                                <a href="{{ $category->url() }}" class="text-brand-600 hover:underline">Сбросить</a>
                            @else
                                В этой категории пока нет товаров.
                            @endif
                        </p>
                    @else
                        @if($children->isNotEmpty())
                            <h2 class="text-xl font-semibold text-slate-900 mb-4">Товары</h2>
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
            {{-- No filters available — render the products grid directly. --}}
            @if($products->isEmpty())
                <p class="mt-10 text-slate-500 italic">В этой категории пока нет товаров.</p>
            @else
                @if($children->isNotEmpty())
                    <h2 class="mt-10 text-xl font-semibold text-slate-900">Товары</h2>
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
