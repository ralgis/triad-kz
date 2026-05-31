@extends('layouts.app', [
    'meta_title' => 'Статьи о ЖБИ — ТРИ АД Construction',
    'meta_description' => 'Полезные статьи о железобетонных изделиях, выборе материалов, нормативах ГОСТ и применении в строительстве.',
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[['label' => 'Статьи', 'url' => url('/blog')]]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ ПУБЛИКАЦИИ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">Статьи</h1>
            <p class="mt-4 text-base text-steel-soft leading-relaxed max-w-2xl">
                Материалы инженерного отдела ТРИ АД: ГОСТы, выбор изделий, расчёты,
                нормативы. Без копирайтерского пафоса.
            </p>
        </header>

        @if($featured->isNotEmpty())
            <section class="mt-10 pb-10 border-b-2 border-edge">
                <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">
                    ━━━━━━━ РЕКОМЕНДУЕМ
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    @foreach($featured as $article)
                        <x-article-card :article="$article" />
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-10 lg:grid lg:grid-cols-12 lg:gap-8">
            <main class="lg:col-span-9">
                @if($articles->isEmpty())
                    <div class="p-6 border-2 border-edge bg-document">
                        <p class="font-mono text-sm text-haze uppercase tracking-wider">
                            ⊘ Статей пока нет. Скоро появятся.
                        </p>
                    </div>
                @else
                    <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">
                        ━━━━━━━ ВСЕ СТАТЬИ
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                        @foreach($articles as $article)
                            <x-article-card :article="$article" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $articles->links() }}
                    </div>
                @endif
            </main>

            <aside class="lg:col-span-3 mt-12 lg:mt-0 space-y-8">
                @if($categories->isNotEmpty())
                    <nav aria-label="Рубрики блога" class="bg-document border-2 border-edge">
                        <p class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm">
                            Рубрики
                        </p>
                        <ul class="divide-y-2 divide-concrete-dark">
                            @foreach($categories as $cat)
                                <li>
                                    <a href="{{ $cat->url() }}"
                                       class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm text-steel hover:bg-concrete hover:text-blueprint-600 transition">
                                        <span>{{ $cat->name }}</span>
                                        @if($cat->articles_count > 0)
                                            <span class="font-mono text-[10px] text-haze uppercase tracking-wider">{{ $cat->articles_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if($popular->isNotEmpty())
                    <section aria-label="Популярное за неделю" class="bg-document border-2 border-edge">
                        <p class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm">
                            Популярное
                        </p>
                        <ul class="divide-y-2 divide-concrete-dark">
                            @foreach($popular as $p)
                                <li>
                                    <a href="{{ $p->url() }}"
                                       class="block px-4 py-2.5 text-sm text-steel hover:bg-concrete hover:text-blueprint-600 transition leading-snug">
                                        {{ $p->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <a href="{{ url('/blog/search') }}"
                   class="block px-4 py-3 bg-document border-2 border-edge font-mono text-[10px] text-haze uppercase tracking-wider hover:bg-concrete hover:text-blueprint-600 transition text-center">
                    ⌕ Поиск по блогу
                </a>
            </aside>
        </div>
    </div>
@endsection
