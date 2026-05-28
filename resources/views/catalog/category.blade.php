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
    </div>
@endsection
