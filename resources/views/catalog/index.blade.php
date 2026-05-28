@extends('layouts.app', [
    'meta_title' => 'Каталог ЖБИ — ТРИ АД Construction',
    'meta_description' => 'Полный каталог железобетонных изделий: кольца, плиты, ФБС, опорные подушки и другая продукция от производителя в Алматы.',
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => 'Каталог', 'url' => url('/catalog')]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">Каталог продукции</h1>
        <p class="mt-2 text-slate-600">Выберите категорию, чтобы посмотреть все товары.</p>

        @if($categories->isEmpty())
            <p class="mt-12 text-slate-500 italic">Каталог временно пуст. Свяжитесь с нами для уточнения.</p>
        @else
            <div class="mt-8 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($categories as $category)
                    <x-category-card :category="$category" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
