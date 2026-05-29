@extends('layouts.app', [
    'meta_title' => 'ГОСТы и Серии — ТРИ АД Construction',
    'meta_description' => 'Справочник государственных стандартов (ГОСТ) и типовых серий, по которым изготавливаются железобетонные изделия ТРИ АД Construction в Казахстане.',
])

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => 'ГОСТы и Серии', 'url' => url('/gosts')]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">
            ГОСТы и Серии
        </h1>

        <p class="mt-3 text-slate-600">
            Технические стандарты, по которым изготавливаются наши изделия. Действующие редакции в Казахстане + исторические редакции, по которым сохраняется маркировка.
        </p>

        @if($gosts->isEmpty())
            <div class="mt-8 p-6 rounded-lg border border-slate-200 bg-slate-50 text-slate-600">
                Справочник пока пустой. Записи добавляются через админ-панель.
            </div>
        @else
            {{--
                Pure Alpine accordion — one root x-data tracks which item is open;
                click on a header toggles it and closes the others. URL hash
                (#slug) auto-opens on page load so links from product cards
                jump straight to the relevant entry.
            --}}
            <ul x-data="{
                    open: window.location.hash ? window.location.hash.slice(1) : null,
                    toggle(slug) { this.open = this.open === slug ? null : slug; window.location.hash = this.open ?? ''; }
                }"
                class="mt-6 space-y-2">
                @foreach($gosts as $gost)
                    <li id="{{ $gost->slug }}"
                        class="border border-slate-200 rounded-lg bg-white overflow-hidden">
                        <button type="button"
                                @click="toggle('{{ $gost->slug }}')"
                                :aria-expanded="open === '{{ $gost->slug }}'"
                                aria-controls="{{ $gost->slug }}-body"
                                class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white text-sm font-semibold transition-transform shrink-0"
                                  :class="open === '{{ $gost->slug }}' ? 'rotate-45' : ''"
                                  aria-hidden="true">+</span>

                            <span class="flex-1 min-w-0">
                                <span class="font-medium text-slate-900">{{ $gost->fullLabel() }}</span>
                                @if($gost->title)
                                    <span class="block text-sm text-slate-500 truncate">{{ $gost->title }}</span>
                                @endif
                            </span>

                            <div class="flex items-center gap-2 shrink-0">
                                @if(! $gost->is_current)
                                    <span class="hidden sm:inline-block text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded">
                                        Устарел
                                    </span>
                                @endif

                                <span @class([
                                    'text-xs uppercase tracking-wide font-semibold',
                                    'text-brand-600' => $gost->kind === \App\Models\Gost::KIND_GOST,
                                    'text-emerald-600' => $gost->kind === \App\Models\Gost::KIND_SERIYA,
                                    'text-amber-600' => $gost->kind === \App\Models\Gost::KIND_TOO,
                                ])>
                                    {{ $gost->kindLabel() }}
                                </span>
                            </div>
                        </button>

                        <div id="{{ $gost->slug }}-body"
                             x-show="open === '{{ $gost->slug }}'"
                             x-cloak
                             x-collapse
                             class="px-4 pb-4 pt-1 text-slate-700">

                            {{-- Status / supersession banner --}}
                            @if(! $gost->is_current)
                                <div class="mb-4 p-3 rounded border border-amber-200 bg-amber-50 text-sm">
                                    <p class="font-medium text-amber-900">
                                        ⚠️ Не является действующей редакцией в Казахстане
                                        @if($gost->effective_in_kz_until)
                                            (действовал до {{ $gost->effective_in_kz_until->format('d.m.Y') }})
                                        @endif
                                    </p>
                                    @if($gost->supersededBy)
                                        <p class="mt-1 text-amber-900">
                                            Заменён: <a href="{{ $gost->supersededBy->url() }}"
                                                        class="font-semibold underline hover:no-underline">{{ $gost->supersededBy->fullLabel() }}</a>
                                        </p>
                                    @elseif($gost->superseded_note)
                                        <p class="mt-1 text-amber-900">{{ $gost->superseded_note }}</p>
                                    @endif
                                </div>
                            @endif

                            {{-- Description body --}}
                            @if($gost->description)
                                <div class="prose prose-slate prose-sm max-w-none">
                                    {!! $gost->description !!}
                                </div>
                            @else
                                <p class="text-slate-500 italic">Описание не заполнено.</p>
                            @endif

                            {{-- Related references: parent ГОСТ for a Серия --}}
                            @if($gost->relatesToGost)
                                <div class="mt-4 p-3 rounded border border-brand-200 bg-brand-50 text-sm">
                                    <p class="text-brand-900">
                                        📋 Разработана в рамках:
                                        <a href="{{ $gost->relatesToGost->url() }}"
                                           class="font-semibold underline hover:no-underline">{{ $gost->relatesToGost->fullLabel() }}</a>
                                    </p>
                                </div>
                            @endif

                            {{-- Reverse — series derived from this ГОСТ --}}
                            @if($gost->series->isNotEmpty())
                                <div class="mt-4 p-3 rounded border border-emerald-200 bg-emerald-50 text-sm">
                                    <p class="font-medium text-emerald-900 mb-1">Связанные серии рабочих чертежей:</p>
                                    <ul class="space-y-0.5">
                                        @foreach($gost->series as $s)
                                            <li>
                                                <a href="{{ $s->url() }}"
                                                   class="text-emerald-800 underline hover:no-underline">{{ $s->fullLabel() }}</a>
                                                @if(! $s->is_current)
                                                    <span class="text-xs text-amber-700">(устарела)</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Footer metadata --}}
                            @if($gost->introduced_at)
                                <p class="mt-4 text-xs text-slate-500">
                                    Введён в действие: {{ $gost->introduced_at->format('d.m.Y') }}
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
