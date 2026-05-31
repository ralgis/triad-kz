@extends('layouts.app', [
    'meta_title' => 'Поиск по блогу — ТРИ АД',
    'meta_description' => 'Поиск статей о ЖБИ-изделиях по блогу ТРИ АД Construction.',
    'noindex' => true,
])

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[
            ['label' => 'Статьи', 'url' => url('/blog')],
            ['label' => 'Поиск', 'url' => url('/blog/search')],
        ]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ ПОИСК ПО БЛОГУ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">Поиск</h1>
        </header>

        <form method="GET" action="{{ url('/blog/search') }}" class="mt-8 flex flex-col sm:flex-row gap-3">
            <input type="search"
                   name="q"
                   value="{{ $q }}"
                   placeholder="например: бетонные кольца"
                   autofocus
                   class="flex-1 bg-document border-2 border-edge px-4 py-3 text-steel placeholder:text-haze focus:border-blueprint-600 focus:outline-none transition">
            <x-button type="submit" variant="primary">Искать →</x-button>
        </form>

        @if($q === '')
            <p class="mt-12 font-mono text-sm text-haze uppercase tracking-wider">
                ━━ Введите слово или фразу выше
            </p>
        @elseif($results->isEmpty())
            <div class="mt-10 p-5 border-2 border-edge bg-document">
                <p class="font-mono text-sm text-haze uppercase tracking-wider">
                    ⊘ По запросу <span class="text-steel spec-value normal-case">«{{ $q }}»</span> ничего не найдено
                </p>
                <p class="mt-3 text-sm text-steel-soft">
                    Попробуйте другую формулировку или зайдите в
                    <a href="{{ url('/blog') }}" class="text-blueprint-600 underline hover:no-underline">список всех статей</a>.
                </p>
            </div>
        @else
            <p class="mt-8 font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">
                ━━ Найдено <span class="spec-value text-steel">{{ $results->count() }}</span> · по запросу «{{ $q }}»
            </p>
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                @foreach($results as $article)
                    <x-article-card :article="$article" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
