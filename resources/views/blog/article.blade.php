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

            <article class="lg:col-span-{{ ! empty($tocItems) ? '9' : '12' }} max-w-3xl"
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

                {{-- TL;DR — admin-marked summary block, hoisted above the
                     main content. AI engines (Perplexity especially) read
                     these short hoisted summaries as the canonical answer
                     for «what is this article about». --}}
                @if($tldr)
                    <aside class="mt-8 rounded-lg bg-amber-50 border border-amber-200 p-5 article-tldr">
                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-800">TL;DR</p>
                        <p class="mt-2 text-slate-800 leading-relaxed">{{ $tldr }}</p>
                    </aside>
                @endif

                @if(! empty($contentHtml))
                    <div class="prose prose-slate mt-8 max-w-none prose-headings:scroll-mt-24">
                        {!! $contentHtml !!}
                    </div>
                @endif

                {{-- FAQ block — only renders when admin populated $article->faq
                     with real Q&A. We mirror the visible content as
                     FAQPage JSON-LD; Google requires the on-page content
                     match the schema (no cloaking). --}}
                @if(! empty($article->faq))
                    <section class="mt-12 pt-8 border-t border-slate-200">
                        <h2 id="faq" class="text-xl font-semibold text-slate-900 mb-6">Часто задаваемые вопросы</h2>
                        <div class="space-y-3">
                            @foreach($article->faq as $qa)
                                <details class="group border border-slate-200 rounded-lg p-4 open:bg-slate-50">
                                    <summary class="cursor-pointer font-medium text-slate-900 list-none flex items-start justify-between gap-4">
                                        <span>{{ $qa['question'] ?? '' }}</span>
                                        <svg class="size-5 text-slate-400 group-open:rotate-180 transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </summary>
                                    <div class="mt-3 text-slate-700 leading-relaxed whitespace-pre-line">
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
                    <section class="mt-12 pt-8 border-t border-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900 mb-6">Пошаговое руководство</h2>
                        <ol class="space-y-6">
                            @foreach($article->how_to_steps as $i => $step)
                                <li class="flex gap-4">
                                    <div class="shrink-0 size-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-sm font-semibold">
                                        {{ $i + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        @if(! empty($step['name']))
                                            <p class="font-medium text-slate-900">{{ $step['name'] }}</p>
                                        @endif
                                        @if(! empty($step['text']))
                                            <p class="mt-1 text-slate-700 leading-relaxed">{{ $step['text'] }}</p>
                                        @endif
                                        @if(! empty($step['image']))
                                            <img src="{{ $step['image'] }}"
                                                 alt="Шаг {{ $i + 1 }}: {{ $step['name'] ?? '' }}"
                                                 class="mt-3 rounded-lg border border-slate-200 max-w-md">
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
                    <section class="mt-12 pt-8 border-t border-slate-200">
                        <h2 class="text-base font-semibold text-slate-900 mb-4">Использованные источники</h2>
                        <ul class="space-y-2 text-sm">
                            @foreach($article->external_sources as $src)
                                <li>
                                    @if(! empty($src['url']))
                                        <a href="{{ $src['url'] }}"
                                           rel="external nofollow noopener"
                                           target="_blank"
                                           class="text-brand-600 hover:underline">
                                            {{ $src['title'] ?? $src['url'] }}
                                        </a>
                                    @else
                                        <span class="text-slate-700">{{ $src['title'] ?? '' }}</span>
                                    @endif
                                    @if(! empty($src['accessed_at']))
                                        <span class="text-slate-400 text-xs">(доступ: {{ $src['accessed_at'] }})</span>
                                    @endif
                                    @if(! empty($src['note']))
                                        <span class="text-slate-500 text-xs">— {{ $src['note'] }}</span>
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
                    <aside class="mt-12 rounded-lg border border-brand-200 bg-brand-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-700">Часть темы</p>
                        <a href="{{ $pillarOfCluster->url() }}"
                           class="mt-1 inline-block text-lg font-medium text-brand-900 hover:underline">
                            {{ $pillarOfCluster->title }}
                        </a>
                        @if($pillarOfCluster->subtitle)
                            <p class="mt-1 text-sm text-brand-700/80">{{ $pillarOfCluster->subtitle }}</p>
                        @endif
                    </aside>
                @endif

                {{-- Clusters-of-pillar: when this is the pillar, list its
                     spokes. Auto-rendered (no Filament validation needed —
                     they always reflect current DB state). --}}
                @if($clustersOfPillar->isNotEmpty())
                    <section class="mt-12 pt-8 border-t border-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900 mb-6">В этой теме</h2>
                        <ul class="space-y-2">
                            @foreach($clustersOfPillar as $cluster)
                                <li>
                                    <a href="{{ $cluster->url() }}"
                                       class="flex items-baseline gap-3 text-slate-700 hover:text-brand-600">
                                        <span class="text-brand-500">→</span>
                                        <span>
                                            <span class="font-medium">{{ $cluster->title }}</span>
                                            @if($cluster->subtitle)
                                                <span class="block text-sm text-slate-500">{{ $cluster->subtitle }}</span>
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
                    <section class="mt-12 pt-8 border-t border-slate-200">
                        <h2 class="text-xl font-semibold text-slate-900 mb-6">Товары из статьи</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                            @foreach($article->products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                    </section>
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
