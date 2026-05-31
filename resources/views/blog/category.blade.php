@extends('layouts.app', [
    'meta_title' => $category->meta_title ?: $category->name.' — статьи блога ТРИ АД',
    'meta_description' => $category->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags($category->description ?? ''), 160)
        ?: 'Статьи рубрики «'.$category->name.'» — блог ТРИ АД о ЖБИ-изделиях.',
    'og_image' => $category->getFirstMediaUrl('cover', 'og') ?: null,
    'og_type' => 'website',
    'schema_jsonld' => view('partials.schema.blog-category', compact('category', 'articles'))->render(),
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[
            ['label' => 'Статьи', 'url' => url('/blog')],
            ['label' => $category->name, 'url' => $category->url()],
        ]" />

        @php($breadcrumbItems = [
            ['label' => 'Статьи', 'url' => url('/blog')],
            ['label' => $category->name, 'url' => $category->url()],
        ])
        @include('partials.schema.breadcrumb', ['items' => $breadcrumbItems])

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ РУБРИКА БЛОГА
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">{{ $category->name }}</h1>
            @if($category->description)
                <div class="prose prose-slate mt-4 max-w-3xl
                            prose-headings:font-display prose-headings:uppercase
                            prose-p:text-steel-soft prose-strong:text-steel
                            prose-a:text-blueprint-600 hover:prose-a:underline
                            prose-li:text-steel-soft">
                    {!! $category->description !!}
                </div>
            @endif
        </header>

        @if($articles->isEmpty())
            <div class="mt-8 p-6 border-2 border-edge bg-document">
                <p class="font-mono text-sm text-haze uppercase tracking-wider">
                    ⊘ В этой рубрике пока нет опубликованных статей.
                </p>
            </div>
        @else
            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
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
