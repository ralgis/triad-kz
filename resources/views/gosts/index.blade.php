@extends('layouts.app', [
    'meta_title' => 'ГОСТы и Серии — ТРИ АД Construction',
    'meta_description' => 'Справочник государственных стандартов (ГОСТ) и типовых серий, по которым изготавливаются железобетонные изделия ТРИ АД Construction в Казахстане.',
])

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[['label' => 'ГОСТы и Серии', 'url' => url('/gosts')]]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ ТЕХНИЧЕСКИЕ СТАНДАРТЫ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">ГОСТы и серии</h1>
            <p class="mt-4 text-base text-steel-soft leading-relaxed max-w-2xl">
                Технические стандарты, по которым изготавливаются наши изделия. Действующие
                редакции в Казахстане + исторические редакции, по которым сохраняется маркировка.
            </p>
        </header>

        @if($gosts->isEmpty())
            <div class="mt-8 p-6 border-2 border-edge bg-document">
                <p class="font-mono text-sm text-haze uppercase tracking-wider">
                    Справочник пока пустой. Записи добавляются через админ-панель.
                </p>
            </div>
        @else
            <ul x-data="{
                    open: window.location.hash ? window.location.hash.slice(1) : null,
                    toggle(slug) { this.open = this.open === slug ? null : slug; window.location.hash = this.open ?? ''; }
                }"
                class="mt-8 space-y-2">
                @foreach($gosts as $gost)
                    @php
                        $kindColor = match ($gost->kind) {
                            \App\Models\Gost::KIND_GOST => 'blueprint',
                            \App\Models\Gost::KIND_SERIYA => 'steel',
                            \App\Models\Gost::KIND_TOO => 'stamp',
                            default => 'haze',
                        };
                    @endphp
                    <li id="{{ $gost->slug }}" class="border-2 border-edge bg-document">
                        <button type="button"
                                @click="toggle('{{ $gost->slug }}')"
                                :aria-expanded="open === '{{ $gost->slug }}'"
                                aria-controls="{{ $gost->slug }}-body"
                                class="w-full flex items-stretch gap-3 px-4 py-3 text-left hover:bg-concrete transition focus:outline-none focus:bg-concrete">
                            <span class="inline-flex items-center justify-center w-7 h-7 bg-steel text-document font-mono text-sm shrink-0 self-center transition-transform"
                                  :class="open === '{{ $gost->slug }}' ? 'rotate-45' : ''"
                                  aria-hidden="true">+</span>

                            <span class="flex-1 min-w-0 self-center">
                                <span class="font-display uppercase tracking-tight text-steel">{{ $gost->fullLabel() }}</span>
                                @if($gost->title)
                                    <span class="block font-mono text-xs text-haze truncate mt-0.5">{{ $gost->title }}</span>
                                @endif
                            </span>

                            <div class="flex items-center gap-2 shrink-0 self-center">
                                @if(! $gost->is_current)
                                    <span class="hidden sm:inline-block font-mono text-[10px] uppercase tracking-wider text-stamp-700 bg-stamp-50 border-2 border-stamp-600 px-2 py-1">
                                        Устарел
                                    </span>
                                @endif

                                <span @class([
                                    'font-mono text-[10px] uppercase tracking-wider px-2 py-1 border-2',
                                    'text-blueprint-700 border-blueprint-600 bg-blueprint-50' => $gost->kind === \App\Models\Gost::KIND_GOST,
                                    'text-steel border-edge bg-document' => $gost->kind === \App\Models\Gost::KIND_SERIYA,
                                    'text-stamp-700 border-stamp-600 bg-stamp-50' => $gost->kind === \App\Models\Gost::KIND_TOO,
                                ])>
                                    {{ $gost->kindLabel() }}
                                </span>
                            </div>
                        </button>

                        <div id="{{ $gost->slug }}-body"
                             x-show="open === '{{ $gost->slug }}'"
                             x-cloak
                             x-collapse
                             class="px-4 pb-4 pt-1 border-t-2 border-concrete-dark">

                            @if(! $gost->is_current)
                                <div class="mb-4 p-3 border-2 border-stamp-600 bg-stamp-50">
                                    <p class="font-mono text-xs uppercase tracking-wider text-stamp-700">
                                        ⊘ Не является действующей редакцией в Казахстане
                                        @if($gost->effective_in_kz_until)
                                            (действовал до {{ $gost->effective_in_kz_until->format('d.m.Y') }})
                                        @endif
                                    </p>
                                    @if($gost->supersededBy)
                                        <p class="mt-2 text-sm text-stamp-700">
                                            Заменён:
                                            <a href="{{ $gost->supersededBy->url() }}"
                                               class="font-mono font-bold underline hover:no-underline">{{ $gost->supersededBy->fullLabel() }}</a>
                                        </p>
                                    @elseif($gost->superseded_note)
                                        <p class="mt-2 text-sm text-stamp-700">{{ $gost->superseded_note }}</p>
                                    @endif
                                </div>
                            @endif

                            @if($gost->description)
                                <div class="prose prose-slate prose-sm max-w-none mt-4
                                            prose-headings:font-display prose-headings:uppercase prose-headings:text-steel
                                            prose-p:text-steel prose-strong:text-steel
                                            prose-a:text-blueprint-600 hover:prose-a:underline
                                            prose-code:font-mono">
                                    {!! $gost->description !!}
                                </div>
                            @else
                                <p class="mt-4 font-mono text-xs text-haze uppercase tracking-wider italic">Описание не заполнено</p>
                            @endif

                            @if($gost->relatesToGost)
                                <div class="mt-4 p-3 border-l-4 border-blueprint-600 bg-blueprint-50">
                                    <p class="font-mono text-[10px] text-blueprint-700 uppercase tracking-wider mb-1">━━━ Разработана в рамках</p>
                                    <a href="{{ $gost->relatesToGost->url() }}"
                                       class="font-display uppercase tracking-tight text-blueprint-900 underline hover:no-underline">
                                        {{ $gost->relatesToGost->fullLabel() }}
                                    </a>
                                </div>
                            @endif

                            @if($gost->series->isNotEmpty())
                                <div class="mt-4 p-3 border-l-4 border-steel bg-document">
                                    <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-2">━━━ Связанные серии рабочих чертежей</p>
                                    <ul class="space-y-1">
                                        @foreach($gost->series as $s)
                                            <li class="text-sm">
                                                <a href="{{ $s->url() }}" class="font-display uppercase tracking-tight text-steel underline hover:no-underline">{{ $s->fullLabel() }}</a>
                                                @if(! $s->is_current)
                                                    <span class="font-mono text-[10px] text-stamp-700 uppercase tracking-wider ml-1">(устарела)</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($gost->introduced_at)
                                <p class="mt-4 font-mono text-[10px] text-haze uppercase tracking-wider">
                                    Введён в действие: <span class="spec-value">{{ $gost->introduced_at->format('d.m.Y') }}</span>
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
