@extends('layouts.app', [
    'meta_title' => 'Каталог ЖБИ — ТРИ АД Construction',
    'meta_description' => 'Полный каталог железобетонных изделий: кольца, плиты, ФБС, опорные подушки и другая продукция от производителя в Алматы.',
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[['label' => 'Каталог', 'url' => url('/catalog')]]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ КАТАЛОГ ЖБИ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">Каталог продукции</h1>
            <p class="mt-4 text-base text-steel-soft leading-relaxed max-w-2xl">
                Выберите категорию, чтобы посмотреть товары и характеристики.
                Все изделия — собственное производство в Алматы по действующим
                ГОСТам.
            </p>
        </header>

        @if($categories->isEmpty())
            <div class="mt-12 p-6 border-2 border-edge bg-document">
                <p class="font-mono text-sm text-haze uppercase tracking-wider">
                    Каталог временно пуст. Свяжитесь с нами для уточнения наличия.
                </p>
            </div>
        @else
            <div class="mt-8 sm:mt-10 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($categories as $category)
                    <x-category-card :category="$category" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
