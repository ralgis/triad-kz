@extends('layouts.app', [
    'meta_title' => 'Контакты — ТРИ АД Construction',
    'meta_description' => 'Свяжитесь с нами: телефон, email, адрес офиса в Алматы. Принимаем заявки на ЖБИ круглосуточно.',
])

@php
    $phoneTel = $settings->phone ? preg_replace('/[^0-9+]/', '', $settings->phone) : null;
    $primaryWa = $settings->whatsappNumbers()[0] ?? null;
    $tg = $settings->telegramUrl();
    $scheduleLines = $settings->workingHoursLines();
@endphp

@section('content')
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[['label' => 'Контакты', 'url' => route('contacts.show')]]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ СВЯЗЬ С ОТДЕЛОМ ПРОДАЖ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">Контакты</h1>
        </header>

        @if($page?->content)
            <div class="prose prose-slate mt-6 max-w-3xl prose-p:text-steel-soft prose-headings:font-display prose-headings:uppercase">
                {!! $page->content !!}
            </div>
        @endif

        <div class="mt-10 grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">

            {{-- Left column: connect block + requisites + map --}}
            <div class="space-y-6">
                <div class="bg-document border-2 border-edge">
                    <div class="bg-steel text-document px-5 sm:px-6 py-3 flex items-center justify-between">
                        <p class="font-display uppercase tracking-wider text-xs sm:text-sm">Связаться напрямую</p>
                        @if(! empty($scheduleLines))
                            <span class="font-mono text-[10px] sm:text-xs text-haze uppercase">
                                @if($settings->isOpenNow())Сейчас открыто@elseЗапись круглосуточно@endif
                            </span>
                        @endif
                    </div>

                    @if($settings->phone)
                        <div class="px-5 sm:px-6 py-5 border-b-2 border-concrete-dark">
                            <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-1">Прямой телефон</p>
                            <p class="font-mono text-xl sm:text-2xl text-steel spec-value leading-tight mb-4">
                                {{ $settings->phone }}
                            </p>
                            <div class="grid grid-cols-{{ $primaryWa ? '2' : '1' }} gap-2">
                                <a href="tel:{{ $phoneTel }}"
                                   class="inline-flex items-center justify-center gap-2 px-3 py-3 border-2 border-edge bg-document font-display uppercase tracking-wider text-xs hover:bg-steel hover:text-document transition">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    Позвонить
                                </a>
                                @if($primaryWa)
                                    <a href="{{ $primaryWa['wa_url'] }}"
                                       class="inline-flex items-center justify-center gap-2 px-3 py-3 border-2 border-edge bg-document font-display uppercase tracking-wider text-xs hover:bg-steel hover:text-document transition">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                        WhatsApp
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($settings->public_email || $tg)
                        <div class="px-5 sm:px-6 py-4 border-b-2 border-concrete-dark">
                            <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-3">Написать</p>
                            @if($tg)
                                <a href="{{ $tg }}"
                                   class="inline-flex items-center gap-2 px-3 py-2 border-2 border-edge font-mono text-xs uppercase tracking-wider hover:bg-blueprint-600 hover:text-document hover:border-blueprint-600 transition mb-3">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                                    Telegram
                                </a>
                            @endif
                            @if($settings->public_email)
                                <a href="mailto:{{ $settings->public_email }}"
                                   class="block font-mono text-xs sm:text-sm text-blueprint-600 hover:text-blueprint-700 hover:underline">
                                    {{ $settings->public_email }}
                                </a>
                            @endif
                        </div>
                    @endif

                    @if($settings->address || $settings->city)
                        <div class="px-5 sm:px-6 py-4 border-b-2 border-concrete-dark">
                            <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-2">Адрес офиса</p>
                            <address class="not-italic text-sm text-steel leading-relaxed">
                                @if($settings->address){{ $settings->address }}<br>@endif
                                {{ trim(($settings->postal_code ?? '').' '.($settings->city ?? '')) }}
                            </address>
                        </div>
                    @endif

                    @if(! empty($scheduleLines))
                        <div class="px-5 sm:px-6 py-4">
                            <p class="font-mono text-[10px] text-haze uppercase tracking-wider mb-2">Часы работы</p>
                            <ul class="space-y-1 font-mono text-sm text-steel">
                                @foreach($scheduleLines as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                            @php($specials = collect($settings->special_days ?? [])->take(3))
                            @if($specials->isNotEmpty())
                                <p class="font-mono text-[10px] text-haze uppercase tracking-wider mt-4 mb-2">Особые дни</p>
                                <ul class="space-y-1 font-mono text-xs text-haze">
                                    @foreach($specials as $sd)
                                        <li>
                                            {{ \Carbon\Carbon::parse($sd['date'])->translatedFormat('d.m.Y') }} —
                                            @if(($sd['status'] ?? '') === 'short' && ! empty($sd['from']) && ! empty($sd['to']))
                                                {{ $sd['from'] }}–{{ $sd['to'] }}
                                            @else
                                                выходной
                                            @endif
                                            @if(! empty($sd['note']))({{ $sd['note'] }})@endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>

                @if($settings->company_legal_name)
                    <div class="bg-document border-2 border-edge">
                        <div class="bg-steel text-document px-5 sm:px-6 py-3">
                            <p class="font-display uppercase tracking-wider text-xs sm:text-sm">Реквизиты компании</p>
                        </div>
                        <dl class="px-5 sm:px-6 py-4 space-y-2 font-mono text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-haze">Юр. лицо</dt><dd class="text-steel text-right">{{ $settings->company_legal_name }}</dd></div>
                            @if($settings->company_bin)
                                <div class="flex justify-between gap-3"><dt class="text-haze">БИН</dt><dd class="spec-value text-steel">{{ $settings->company_bin }}</dd></div>
                            @endif
                            @if($settings->company_legal_address)
                                <div class="flex justify-between gap-3"><dt class="text-haze">Юр. адрес</dt><dd class="text-steel text-right">{{ $settings->company_legal_address }}</dd></div>
                            @endif
                        </dl>
                    </div>
                @endif

                @if($settings->map_lat && $settings->map_lng)
                    <div class="bg-document border-2 border-edge overflow-hidden">
                        <div class="bg-steel text-document px-5 sm:px-6 py-3">
                            <p class="font-display uppercase tracking-wider text-xs sm:text-sm">Расположение</p>
                        </div>
                        <div x-data="contactsMap({ lat: {{ $settings->map_lat }}, lng: {{ $settings->map_lng }}, label: @js($settings->address ?? '') })"
                             x-init="init()"
                             wire:ignore>
                            <div x-ref="map" class="h-72 w-full bg-concrete-dark"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right column: contact form --}}
            <div class="bg-document border-2 border-edge">
                <div class="bg-steel text-document px-5 sm:px-6 py-3">
                    <p class="font-display uppercase tracking-wider text-xs sm:text-sm">Оставить заявку</p>
                </div>

                <div class="px-5 sm:px-6 py-5">
                    @if(session('contact.sent'))
                        <div class="mb-4 p-4 bg-blueprint-50 border-2 border-blueprint-600 font-mono text-sm text-blueprint-900">
                            ✓ {{ session('contact.sent') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contacts.store') }}" class="space-y-4">
                        @csrf

                        @if($product)
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="bg-stamp-50 border-2 border-stamp-600 p-3 font-mono text-xs text-stamp-700 uppercase tracking-wider">
                                ━━━ Запрос по товару<br>
                                <span class="text-steel normal-case tracking-normal text-sm mt-1 block">{{ $product->name }}</span>
                            </div>
                        @endif

                        <x-input name="name" label="Ваше имя" required maxlength="120" autocomplete="name" />
                        <x-input name="phone" type="tel" label="Телефон" required placeholder="+7XXXXXXXXXX" autocomplete="tel" />
                        <x-input name="email" type="email" label="Email" maxlength="120" autocomplete="email"
                                 help="Для коммерческого предложения (опционально)." />
                        <x-textarea name="message" label="Сообщение" rows="4" maxlength="2000"
                                    help="Опишите запрос или количество.">{{ $product ? 'Прошу выслать цену и условия по '.$product->name : '' }}</x-textarea>

                        <x-button type="submit" variant="primary" size="lg" class="w-full">
                            Отправить заявку →
                        </x-button>

                        <p class="font-mono text-[10px] text-haze uppercase tracking-wider text-center">
                            Отправляя форму, вы соглашаетесь с обработкой персональных данных
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
