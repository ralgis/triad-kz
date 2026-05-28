@extends('layouts.app', [
    'meta_title' => 'Статьи о ЖБИ — ТРИ АД Construction',
    'meta_description' => 'Полезные статьи о железобетонных изделиях, выборе материалов, нормативах ГОСТ и применении в строительстве.',
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => 'Статьи', 'url' => url('/blog')]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">Статьи</h1>
        <p class="mt-2 text-slate-600">Материалы о ЖБИ, ГОСТах и применении в строительстве.</p>

        @if($articles->isEmpty())
            <p class="mt-12 text-slate-500 italic">Статей пока нет. Скоро появятся.</p>
        @else
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($articles as $article)
                    <x-article-card :article="$article" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
@endsection
