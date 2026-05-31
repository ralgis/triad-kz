@extends('layouts.app', [
    'meta_title' => $article->meta_title ?: $article->title.' — статьи ТРИ АД',
    'meta_description' => $article->meta_description
        ?: $article->excerpt
        ?: \Illuminate\Support\Str::limit(strip_tags($article->content ?? ''), 160),
    'og_image' => $article->getFirstMediaUrl('cover', 'og') ?: null,
    'og_type' => 'article',
    'schema_jsonld' => view('partials.schema.article', compact('article'))->render()
        .(! empty($article->faq) ? view('partials.schema.faq', ['faq' => $article->faq])->render() : '')
        .(! empty($article->how_to_steps) ? view('partials.schema.howto', ['article' => $article, 'steps' => $article->how_to_steps])->render() : ''),
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
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="$breadcrumbItems" />
        @include('partials.schema.breadcrumb', ['items' => $breadcrumbItems])

        <div class="mt-6 lg:grid lg:grid-cols-12 lg:gap-8">

            {{-- TOC sidebar — sticky on desktop. Hidden on mobile (could
                 surface as a collapse later; not P0). --}}
            @if(! empty($tocItems))
                <aside class="hidden lg:block lg:col-span-3 lg:sticky lg:top-24 self-start">
                    <nav aria-label="Содержание статьи" class="bg-document border-2 border-edge">
                        <p class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs">
                            Содержание
                        </p>
                        <ul class="px-4 py-3 space-y-1.5">
                            @foreach($tocItems as $item)
                                <li @class([
                                    'leading-snug',
                                    'pl-3 text-haze' => $item['level'] === 3,
                                ])>
                                    <a href="#{{ $item['id'] }}"
                                       class="block py-1 font-mono text-[11px] uppercase tracking-wider text-steel-soft hover:text-blueprint-600 transition">
                                        {{ $item['text'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </aside>
            @endif

            <article @class([
                        'max-w-3xl',
                        'lg:col-span-9' => ! empty($tocItems),
                        'lg:col-span-12' => empty($tocItems),
                     ])
                     x-data="{
                         depthFired: { 25: false, 50: false, 75: false, 100: false },
                         fireDepth(pct) {
                             if (this.depthFired[pct]) return;
                             this.depthFired[pct] = true;
                             if (typeof ym === 'function' && {{ \App\Models\Setting::current()->analytics_yandex_id ? 'true' : 'false' }}) {
                                 ym({{ \App\Models\Setting::current()->analytics_yandex_id ?: 0 }}, 'reachGoal', 'article_read_' + pct + 'pct');
                             }
                             if (typeof gtag === 'function') {
                                 gtag('event', 'article_read_' + pct + 'pct', { article_slug: '{{ $article->slug }}' });
                             }
                         },
                         onScroll() {
                             const el = this.$el;
                             const top = el.getBoundingClientRect().top;
                             const height = el.offsetHeight;
                             const winH = window.innerHeight;
                             const read = Math.min(100, Math.max(0, ((winH - top) / height) * 100));
                             [25, 50, 75, 100].forEach(p => { if (read >= p) this.fireDepth(p); });
                         }
                     }"
                     x-init="window.addEventListener('scroll', onScroll, { passive: true })">

                <header class="pb-6 border-b-2 border-edge">
                    @if($article->blogCategory)
                        <a href="{{ $article->blogCategory->url() }}"
                           class="inline-block font-mono text-[10px] uppercase tracking-wider px-2 py-1 border-2 border-blueprint-600 bg-blueprint-50 text-blueprint-700 hover:bg-blueprint-100 transition">
                            {{ $article->blogCategory->name }}
                        </a>
                    @endif

                    <h1 class="mt-4 text-3xl sm:text-4xl lg:text-5xl uppercase leading-tight">
                        {{ $article->title }}
                    </h1>

                    @if($article->subtitle)
                        <p class="mt-3 text-base sm:text-lg text-steel-soft leading-relaxed">{{ $article->subtitle }}</p>
                    @endif

                    {{-- Byline — published date, updated marker (only when
                         actually distinct), reading time, word count. All
                         monospace tabular-nums so the row aligns across
                         article cards on related lists. --}}
                    <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">
                        @if($article->published_at)
                            <time datetime="{{ $article->published_at->toIso8601String() }}" class="spec-value">
                                {{ $article->published_at->translatedFormat('j M Y') }}
                            </time>
                        @endif

                        @if($article->updated_content_at && $article->updated_content_at->gt($article->published_at ?? now()))
                            <span title="Дата значимого обновления контента">
                                · обновлено
                                <time datetime="{{ $article->updated_content_at->toIso8601String() }}" class="spec-value">
                                    {{ $article->updated_content_at->translatedFormat('j M Y') }}
                                </time>
                            </span>
                        @endif

                        @if($article->reading_minutes)
                            <span>· <span class="spec-value">{{ $article->reading_minutes }}</span> мин</span>
                        @endif

                        @if($article->word_count)
                            <span class="text-haze/60">· <span class="spec-value">{{ $article->word_count }}</span> слов</span>
                        @endif
                    </div>

                    @if($article->excerpt)
                        <p class="mt-6 text-base sm:text-lg text-steel-soft leading-relaxed border-l-4 border-blueprint-600 bg-document py-3 px-4">
                            {{ $article->excerpt }}
                        </p>
                    @endif
                </header>

                @php($cover = $article->getFirstMediaUrl('cover', 'hero'))
                @if($cover)
                    <figure class="mt-8 border-2 border-edge bg-concrete-dark overflow-hidden">
                        <img src="{{ $cover }}"
                             alt="{{ $article->imageAlt() }}"
                             title="{{ $article->imageTitle() }}"
                             class="w-full h-auto">
                    </figure>
                @endif

                {{-- TL;DR — admin-marked summary block, hoisted above the
                     main content. AI engines (Perplexity especially) read
                     these short hoisted summaries as the canonical answer
                     for «what is this article about». --}}
                @if($tldr)
                    <aside class="mt-8 bg-blueprint-50 border-2 border-blueprint-600 p-5 article-tldr">
                        <p class="font-mono text-[10px] sm:text-xs uppercase tracking-wider text-blueprint-700">━━ TL;DR · краткое резюме</p>
                        <p class="mt-2 text-steel leading-relaxed">{{ $tldr }}</p>
                    </aside>
                @endif

                @if(! empty($contentHtml))
                    <div class="prose prose-slate mt-8 max-w-none prose-headings:scroll-mt-24
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
                        {!! $contentHtml !!}
                    </div>
                @endif

                {{-- FAQ block — renders when admin populated $article->faq
                     with real Q&A. Mirrors the visible content as FAQPage
                     JSON-LD; Google requires the on-page content match
                     the schema (no cloaking). --}}
                @if(! empty($article->faq))
                    <section class="mt-12 pt-8 border-t-2 border-edge">
                        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">━━━━━━━ ЧАСТО ЗАДАВАЕМЫЕ ВОПРОСЫ</p>
                        <h2 id="faq" class="text-2xl sm:text-3xl uppercase mb-6">FAQ</h2>
                        <div class="space-y-3">
                            @foreach($article->faq as $qa)
                                <details class="group border-2 border-edge bg-document open:bg-concrete">
                                    <summary class="cursor-pointer p-4 font-medium text-steel list-none flex items-start justify-between gap-4">
                                        <span>{{ $qa['question'] ?? '' }}</span>
                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-steel text-document font-mono text-sm shrink-0 transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                                    </summary>
                                    <div class="px-4 pb-4 text-steel-soft leading-relaxed whitespace-pre-line">
                                        {{ $qa['answer'] ?? '' }}
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- HowTo block — for guide articles describing step-by-step
                     procedures. Renders the steps as an ordered list with
                     optional images. JSON-LD emitted in the layout head. --}}
                @if(! empty($article->how_to_steps))
                    <section class="mt-12 pt-8 border-t-2 border-edge">
                        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">━━━━━━━ ПОШАГОВОЕ РУКОВОДСТВО</p>
                        <h2 class="text-2xl sm:text-3xl uppercase mb-6">Как сделать</h2>
                        <ol class="space-y-6">
                            @foreach($article->how_to_steps as $i => $step)
                                <li class="flex gap-4">
                                    <div class="shrink-0 w-9 h-9 bg-blueprint-600 text-document flex items-center justify-center font-mono spec-value text-sm">
                                        {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="flex-1">
                                        @if(! empty($step['name']))
                                            <p class="font-display uppercase tracking-tight text-base sm:text-lg text-steel">{{ $step['name'] }}</p>
                                        @endif
                                        @if(! empty($step['text']))
                                            <p class="mt-1 text-steel leading-relaxed">{{ $step['text'] }}</p>
                                        @endif
                                        @if(! empty($step['image']))
                                            <img src="{{ $step['image'] }}"
                                                 alt="Шаг {{ $i + 1 }}: {{ $step['name'] ?? '' }}"
                                                 class="mt-3 border-2 border-edge bg-concrete-dark max-w-md">
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                {{-- External sources — E-E-A-T trust signal for readers.
                     rel="external nofollow noopener" on all links: no
                     PageRank leak, no window.opener exposure, marked
                     external. --}}
                @if(! empty($article->external_sources))
                    <section class="mt-12 pt-8 border-t-2 border-edge">
                        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">━━━━━━━ ИСПОЛЬЗОВАННЫЕ ИСТОЧНИКИ</p>
                        <ul class="space-y-2 text-sm bg-document border-2 border-edge divide-y-2 divide-concrete-dark">
                            @foreach($article->external_sources as $src)
                                <li class="px-4 py-2.5">
                                    @if(! empty($src['url']))
                                        <a href="{{ $src['url'] }}"
                                           rel="external nofollow noopener"
                                           target="_blank"
                                           class="text-blueprint-600 hover:underline">
                                            {{ $src['title'] ?? $src['url'] }}
                                        </a>
                                    @else
                                        <span class="text-steel">{{ $src['title'] ?? '' }}</span>
                                    @endif
                                    @if(! empty($src['accessed_at']))
                                        <span class="font-mono text-[10px] text-haze uppercase tracking-wider">(доступ: <span class="spec-value">{{ $src['accessed_at'] }}</span>)</span>
                                    @endif
                                    @if(! empty($src['note']))
                                        <span class="text-steel-soft text-xs">— {{ $src['note'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- Pillar-of-cluster: when this article is a cluster, link
                     back to its pillar. Title-as-anchor keeps the SEO weight
                     concentrated on the topic keyword. --}}
                @if($pillarOfCluster)
                    <aside class="mt-12 border-2 border-blueprint-600 bg-blueprint-50 p-5">
                        <p class="font-mono text-[10px] uppercase tracking-wider text-blueprint-700">━━ Часть темы</p>
                        <a href="{{ $pillarOfCluster->url() }}"
                           class="mt-2 inline-block font-display uppercase tracking-tight text-lg sm:text-xl text-blueprint-900 hover:underline">
                            {{ $pillarOfCluster->title }}
                        </a>
                        @if($pillarOfCluster->subtitle)
                            <p class="mt-1 text-sm text-blueprint-700/80">{{ $pillarOfCluster->subtitle }}</p>
                        @endif
                    </aside>
                @endif

                {{-- Clusters-of-pillar: when this is the pillar, list its
                     spokes. Auto-rendered (no Filament validation needed —
                     they always reflect current DB state). --}}
                @if($clustersOfPillar->isNotEmpty())
                    <section class="mt-12 pt-8 border-t-2 border-edge">
                        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">━━━━━━━ В ЭТОЙ ТЕМЕ</p>
                        <ul class="bg-document border-2 border-edge divide-y-2 divide-concrete-dark">
                            @foreach($clustersOfPillar as $cluster)
                                <li>
                                    <a href="{{ $cluster->url() }}"
                                       class="flex items-baseline gap-3 px-4 py-3 hover:bg-concrete transition">
                                        <span class="text-blueprint-600 font-mono">→</span>
                                        <span>
                                            <span class="font-display uppercase tracking-tight text-steel hover:text-blueprint-600">{{ $cluster->title }}</span>
                                            @if($cluster->subtitle)
                                                <span class="block text-sm text-steel-soft mt-0.5">{{ $cluster->subtitle }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- Related products from article_product M2M — drives the
                     blog→catalog funnel. Distinct from $related (which is
                     same-rubric articles). --}}
                @if($article->products->isNotEmpty())
                    <section class="mt-12 pt-8 border-t-2 border-edge">
                        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">━━━━━━━ ТОВАРЫ ИЗ СТАТЬИ</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                            @foreach($article->products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($related->isNotEmpty())
                    <section class="mt-12 pt-8 border-t-2 border-edge">
                        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-4">
                            @if($article->blogCategory)
                                ━━━━━━━ ТАКЖЕ В РУБРИКЕ «{{ mb_strtoupper($article->blogCategory->name) }}»
                            @else
                                ━━━━━━━ ПОХОЖИЕ СТАТЬИ
                            @endif
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                            @foreach($related as $sibling)
                                <x-article-card :article="$sibling" />
                            @endforeach
                        </div>
                    </section>
                @endif

                <footer class="mt-12 pt-6 border-t-2 border-edge flex flex-col sm:flex-row gap-3">
                    <x-button :href="$article->blogCategory?->url() ?: url('/blog')" variant="outline">
                        ← К рубрике
                    </x-button>
                    <x-button :href="url('/contacts')" variant="primary">
                        Связаться с инженером →
                    </x-button>
                </footer>
            </article>
        </div>
    </div>
@endsection
