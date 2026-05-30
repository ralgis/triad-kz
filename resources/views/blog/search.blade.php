@extends('layouts.app', [
    'meta_title' => 'Поиск по блогу — ТРИ АД',
    'meta_description' => 'Поиск статей о ЖБИ-изделиях по блогу ТРИ АД Construction.',
    'noindex' => true,
])

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[
            ['label' => 'Статьи', 'url' => url('/blog')],
            ['label' => 'Поиск', 'url' => url('/blog/search')],
        ]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">Поиск по блогу</h1>

        <form method="GET" action="{{ url('/blog/search') }}" class="mt-8 flex gap-3">
            <input type="search"
                   name="q"
                   value="{{ $q }}"
                   placeholder="Введите запрос — например, «бетонные кольца»"
                   autofocus
                   class="flex-1 rounded border-slate-300 px-4 py-3 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            <x-button type="submit" variant="primary">Искать</x-button>
        </form>

        @if($q === '')
            <p class="mt-12 text-slate-500 italic">Введите слово или фразу выше.</p>
        @elseif($results->isEmpty())
            <p class="mt-12 text-slate-500">По запросу <strong>«{{ $q }}»</strong> ничего не найдено.</p>
            <p class="mt-2 text-sm text-slate-400">
                Попробуйте другую формулировку или проверьте раздел <a href="{{ url('/blog') }}" class="text-brand-600 hover:underline">всех статей</a>.
            </p>
        @else
            <p class="mt-8 text-sm text-slate-500">Найдено {{ $results->count() }} статей по запросу «{{ $q }}»:</p>
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                @foreach($results as $article)
                    <x-article-card :article="$article" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
