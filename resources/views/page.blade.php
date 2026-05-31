@extends('layouts.app', [
    'meta_title' => $page->meta_title ?: $page->title.' — ТРИ АД Construction',
    'meta_description' => $page->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags($page->content ?? ''), 160),
    'noindex' => $page->noindex,
])

@section('content')
    <article class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[['label' => $page->title, 'url' => $page->url()]]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ {{ mb_strtoupper($page->title) }}
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">{{ $page->title }}</h1>
        </header>

        @if($page->content)
            <div class="prose prose-slate mt-8 max-w-none
                        prose-headings:font-display prose-headings:uppercase prose-headings:tracking-tight prose-headings:text-steel
                        prose-h2:text-2xl prose-h2:sm:text-3xl prose-h2:mt-12 prose-h2:mb-4 prose-h2:pb-2 prose-h2:border-b-2 prose-h2:border-concrete-dark
                        prose-h3:text-xl prose-h3:sm:text-2xl prose-h3:mt-8 prose-h3:mb-3
                        prose-p:text-steel prose-p:leading-relaxed
                        prose-a:text-blueprint-600 prose-a:no-underline hover:prose-a:underline
                        prose-strong:text-steel prose-strong:font-bold
                        prose-li:text-steel
                        prose-blockquote:border-l-4 prose-blockquote:border-blueprint-600 prose-blockquote:bg-document prose-blockquote:py-1 prose-blockquote:px-4 prose-blockquote:not-italic prose-blockquote:text-steel-soft
                        prose-table:border-2 prose-table:border-edge
                        prose-th:bg-document prose-th:font-display prose-th:uppercase prose-th:tracking-wider prose-th:text-xs prose-th:text-haze
                        prose-td:font-mono prose-td:text-sm
                        prose-code:font-mono prose-code:text-blueprint-700">
                {!! $page->content !!}
            </div>
        @endif
    </article>
@endsection
