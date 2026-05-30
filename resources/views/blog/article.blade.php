@extends('layouts.app', [
    'meta_title' => $article->meta_title ?: $article->title.' — статьи ТРИ АД',
    'meta_description' => $article->meta_description
        ?: $article->excerpt
        ?: \Illuminate\Support\Str::limit(strip_tags($article->content ?? ''), 160),
    'og_image' => $article->getFirstMediaUrl('cover', 'og') ?: null,
    'og_type' => 'article',
    'schema_jsonld' => view('partials.schema.article', compact('article'))->render(),
])

@php
    $breadcrumbItems = collect([
        ['label' => 'Статьи', 'url' => url('/blog')],
        $article->blogCategory
            ? ['label' => $article->blogCategory->name, 'url' => $article->blogCategory->url()]
            : null,
        ['label' => $article->title, 'url' => $article->url()],
    ])->filter()->values()->all();
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="$breadcrumbItems" />
        @include('partials.schema.breadcrumb', ['items' => $breadcrumbItems])

        <div class="mt-6 lg:grid lg:grid-cols-12 lg:gap-10">

            {{-- TOC sidebar — sticky on desktop. Hidden on mobile (could
                 surface as a collapse later; not P0). --}}
            @if(! empty($tocItems))
                <aside class="hidden lg:block lg:col-span-3 lg:sticky lg:top-24 self-start">
                    <nav aria-label="Содержание статьи" class="text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
                            Содержание
                        </p>
                        <ul class="space-y-2">
                            @foreach($tocItems as $item)
                                <li @class([
                                    'leading-snug',
                                    'pl-3 text-slate-600' => $item['level'] === 3,
                                ])>
                                    <a href="#{{ $item['id'] }}"
                                       class="block py-1 text-slate-700 hover:text-brand-600 transition">
                                        {{ $item['text'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </aside>
            @endif

            <article class="lg:col-span-{{ ! empty($tocItems) ? '9' : '12' }} max-w-3xl">
                <header>
                    @if($article->blogCategory)
                        <a href="{{ $article->blogCategory->url() }}"
                           class="inline-block text-xs font-semibold uppercase tracking-wider px-2 py-1 rounded bg-brand-50 text-brand-700 hover:bg-brand-100">
                            {{ $article->blogCategory->name }}
                        </a>
                    @endif

                    <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900 leading-tight">
                        {{ $article->title }}
                    </h1>

                    @if($article->subtitle)
                        <p class="mt-3 text-lg text-slate-600 leading-relaxed">{{ $article->subtitle }}</p>
                    @endif

                    {{-- Byline: published date, updated marker (only when
                         actually distinct), reading time. Publisher-only
                         attribution — no author byline (publisher chip
                         lives in footer if needed). --}}
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                        @if($article->published_at)
                            <time datetime="{{ $article->published_at->toIso8601String() }}">
                                {{ $article->published_at->translatedFormat('j F Y') }}
                            </time>
                        @endif

                        @if($article->updated_content_at && $article->updated_content_at->gt($article->published_at ?? now()))
                            <span title="Дата значимого обновления контента">
                                · обновлено
                                <time datetime="{{ $article->updated_content_at->toIso8601String() }}">
                                    {{ $article->updated_content_at->translatedFormat('j F Y') }}
                                </time>
                            </span>
                        @endif

                        @if($article->reading_minutes)
                            <span>· {{ $article->reading_minutes }} мин чтения</span>
                        @endif

                        @if($article->word_count)
                            <span class="text-slate-400">· {{ $article->word_count }} слов</span>
                        @endif
                    </div>

                    @if($article->excerpt)
                        <p class="mt-6 text-lg text-slate-600 leading-relaxed border-l-4 border-brand-200 pl-4">
                            {{ $article->excerpt }}
                        </p>
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

                @if(! empty($contentHtml))
                    <div class="prose prose-slate mt-8 max-w-none prose-headings:scroll-mt-24">
                        {!! $contentHtml !!}
                    </div>
                @endif

                @if($related->isNotEmpty())
                    <section class="mt-16 pt-8 border-t border-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900 mb-6">
                            @if($article->blogCategory)
                                Также в рубрике «{{ $article->blogCategory->name }}»
                            @else
                                Похожие статьи
                            @endif
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            @foreach($related as $sibling)
                                <x-article-card :article="$sibling" />
                            @endforeach
                        </div>
                    </section>
                @endif

                <footer class="mt-12 pt-6 border-t border-slate-200 flex flex-col sm:flex-row gap-3">
                    <x-button :href="$article->blogCategory?->url() ?: url('/blog')" variant="outline">
                        ← К рубрике
                    </x-button>
                    <x-button :href="url('/contacts')" variant="primary">
                        Связаться с инженером
                    </x-button>
                </footer>
            </article>
        </div>
    </div>
@endsection
