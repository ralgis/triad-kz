@extends('layouts.app', [
    'meta_title' => 'ТРИ АД Construction — ЖБИ в Алматы',
    'meta_description' => 'Производство и продажа железобетонных изделий: '
        .'бетонные кольца, плиты перекрытия, ФБС, опорные подушки. Доставка по Казахстану.',
])

@section('content')
    <section class="bg-slate-50 border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="max-w-3xl">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-semibold text-slate-900 leading-tight">
                    Железобетонные изделия в&nbsp;Алматы
                </h1>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                    Кольца, плиты, ФБС, опорные подушки. Полное соответствие ГОСТ.
                    Доставка по Казахстану.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <x-button :href="url('/catalog')" variant="primary" size="lg">
                        Перейти в каталог
                    </x-button>
                    <x-button :href="url('/contacts')" variant="outline" size="lg">
                        Связаться с нами
                    </x-button>
                </div>
            </div>
        </div>
    </section>

    @if($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="flex items-end justify-between gap-4 mb-8">
                <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900">Категории</h2>
                <a href="{{ url('/catalog') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium whitespace-nowrap">
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

    @if($products->isNotEmpty())
        <section class="bg-slate-50 py-12 lg:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900 mb-8">Рекомендуем</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($articles->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="flex items-end justify-between gap-4 mb-8">
                <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900">Статьи</h2>
                <a href="{{ url('/blog') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium whitespace-nowrap">
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
