@extends('layouts.app', [
    'meta_title' => $page->meta_title ?: $page->title.' — ТРИ АД Construction',
    'meta_description' => $page->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags($page->content ?? ''), 160),
    'noindex' => $page->noindex,
])

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => $page->title, 'url' => $page->url()]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">{{ $page->title }}</h1>

        @if($page->content)
            <div class="prose prose-slate mt-6 max-w-none">
                {!! $page->content !!}
            </div>
        @endif
    </div>
@endsection
