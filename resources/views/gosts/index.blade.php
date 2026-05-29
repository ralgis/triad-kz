@extends('layouts.app', [
    'meta_title' => 'ГОСТы и Серии — ТРИ АД Construction',
    'meta_description' => 'Справочник государственных стандартов (ГОСТ) и типовых серий, по которым изготавливаются железобетонные изделия ТРИ АД Construction.',
])

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => 'ГОСТы и Серии', 'url' => url('/gosts')]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">
            ГОСТы и Серии
        </h1>

        <p class="mt-3 text-slate-600">
            Технические стандарты, по которым изготавливаются наши изделия. Кликните по записи, чтобы развернуть описание.
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
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white text-sm font-semibold transition-transform"
                                  :class="open === '{{ $gost->slug }}' ? 'rotate-45' : ''"
                                  aria-hidden="true">+</span>

                            <span class="flex-1 font-medium text-slate-900">{{ $gost->label }}</span>

                            <span class="text-xs uppercase tracking-wide font-semibold {{ $gost->kind === \App\Models\Gost::KIND_GOST ? 'text-brand-600' : 'text-emerald-600' }}">
                                {{ $gost->kindLabel() }}
                            </span>
                        </button>

                        <div id="{{ $gost->slug }}-body"
                             x-show="open === '{{ $gost->slug }}'"
                             x-cloak
                             x-collapse
                             class="px-4 pb-4 pt-1 text-slate-700">
                            @if($gost->description)
                                <div class="prose prose-slate prose-sm max-w-none">
                                    {!! $gost->description !!}
                                </div>
                            @else
                                <p class="text-slate-500 italic">Описание не заполнено.</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
