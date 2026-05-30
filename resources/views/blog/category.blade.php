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
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[
            ['label' => 'Статьи', 'url' => url('/blog')],
            ['label' => $category->name, 'url' => $category->url()],
        ]" />

        @php($breadcrumbItems = [
            ['label' => 'Статьи', 'url' => url('/blog')],
            ['label' => $category->name, 'url' => $category->url()],
        ])
        @include('partials.schema.breadcrumb', ['items' => $breadcrumbItems])

        <header class="mt-6 max-w-3xl">
            <h1 class="text-3xl sm:text-4xl font-semibold text-slate-900">{{ $category->name }}</h1>
            @if($category->description)
                <div class="prose prose-slate mt-4 max-w-none">
                    {!! $category->description !!}
                </div>
            @endif
        </header>

        @if($articles->isEmpty())
            <p class="mt-12 text-slate-500 italic">В этой рубрике пока нет опубликованных статей.</p>
        @else
            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
