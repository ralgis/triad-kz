@extends('layouts.app', [
    'meta_title' => $article->meta_title ?: $article->title.' — статьи ТРИ АД',
    'meta_description' => $article->meta_description
        ?: $article->excerpt
        ?: \Illuminate\Support\Str::limit(strip_tags($article->content ?? ''), 160),
    'og_image' => $article->getFirstMediaUrl('cover', 'og') ?: null,
    'og_type' => 'article',
    'schema_jsonld' => view('partials.schema.article', compact('article'))->render(),
])

@section('content')
    <article class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[
            ['label' => 'Статьи', 'url' => url('/blog')],
            ['label' => $article->title, 'url' => $article->url()],
        ]" />

        <header class="mt-6">
            @if($article->published_at)
                <p class="text-sm text-slate-500 uppercase tracking-wider">
                    {{ $article->published_at->translatedFormat('j F Y') }}
                </p>
            @endif
            <h1 class="mt-2 text-3xl sm:text-4xl font-semibold text-slate-900 leading-tight">
                {{ $article->title }}
            </h1>
            @if($article->excerpt)
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">{{ $article->excerpt }}</p>
            @endif
        </header>

        @php($cover = $article->getFirstMediaUrl('cover', 'hero'))
        @if($cover)
            <figure class="mt-8 rounded-lg overflow-hidden bg-slate-50">
                <img src="{{ $cover }}"
                     alt="{{ $article->imageAlt() }}"
                     title="{{ $article->imageTitle() }}"
                     class="w-full h-auto">
            </figure>
        @endif

        @if($article->content)
            <div class="prose prose-slate mt-8 max-w-none">
                {!! $article->content !!}
            </div>
        @endif

        <footer class="mt-12 pt-6 border-t border-slate-200 flex flex-col sm:flex-row gap-3">
            <x-button :href="url('/blog')" variant="outline">
                ← Все статьи
            </x-button>
            <x-button :href="url('/contacts')" variant="primary">
                Связаться с нами
            </x-button>
        </footer>
    </article>
@endsection
