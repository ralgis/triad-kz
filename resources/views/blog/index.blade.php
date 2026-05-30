@extends('layouts.app', [
    'meta_title' => 'Статьи о ЖБИ — ТРИ АД Construction',
    'meta_description' => 'Полезные статьи о железобетонных изделиях, выборе материалов, нормативах ГОСТ и применении в строительстве.',
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => 'Статьи', 'url' => url('/blog')]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">Статьи</h1>
        <p class="mt-2 text-slate-600">Материалы о ЖБИ, ГОСТах и применении в строительстве.</p>

        <div class="mt-10 lg:grid lg:grid-cols-12 lg:gap-10">
            <main class="lg:col-span-9">
                @if($articles->isEmpty())
                    <p class="mt-4 text-slate-500 italic">Статей пока нет. Скоро появятся.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($articles as $article)
                            <x-article-card :article="$article" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $articles->links() }}
                    </div>
                @endif
            </main>

            @if($categories->isNotEmpty())
                <aside class="lg:col-span-3 mt-12 lg:mt-0">
                    <nav aria-label="Рубрики блога">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
                            Рубрики
                        </p>
                        <ul class="space-y-1">
                            @foreach($categories as $cat)
                                <li>
                                    <a href="{{ $cat->url() }}"
                                       class="flex items-center justify-between gap-3 px-3 py-2 rounded text-sm text-slate-700 hover:bg-slate-100 hover:text-brand-700 transition">
                                        <span>{{ $cat->name }}</span>
                                        @if($cat->articles_count > 0)
                                            <span class="text-xs text-slate-400">{{ $cat->articles_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </aside>
            @endif
        </div>
    </div>
@endsection
