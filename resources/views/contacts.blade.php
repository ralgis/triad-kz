@extends('layouts.app', [
    'meta_title' => 'Контакты — ТРИ АД Construction',
    'meta_description' => 'Свяжитесь с нами: телефон, email, адрес офиса в Алматы. Принимаем заявки на ЖБИ круглосуточно.',
])

@php
    $phoneTel = $settings->phone ? preg_replace('/[^0-9+]/', '', $settings->phone) : null;
@endphp

@section('content')
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => 'Контакты', 'url' => route('contacts.show')]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">Контакты</h1>

        @if($page?->content)
            <div class="prose prose-slate mt-4 max-w-3xl">
                {!! $page->content !!}
            </div>
        @endif

        <div class="mt-10 grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

            <div class="space-y-6">
                <div class="bg-white border border-slate-200 rounded-lg p-6">
                    <h2 class="font-semibold text-slate-900 mb-4">Связаться напрямую</h2>
                    <dl class="space-y-3">
                        @if($settings->phone)
                            <div>
                                <dt class="text-sm text-slate-500">Телефон</dt>
                                <dd class="mt-1"><a href="tel:{{ $phoneTel }}" class="text-lg font-medium text-brand-700 hover:text-brand-800">{{ $settings->phone }}</a></dd>
                            </div>
                        @endif
                        @if($settings->public_email)
                            <div>
                                <dt class="text-sm text-slate-500">Email</dt>
                                <dd class="mt-1"><a href="mailto:{{ $settings->public_email }}" class="text-brand-700 hover:text-brand-800">{{ $settings->public_email }}</a></dd>
                            </div>
                        @endif
                        @if($settings->address)
                            <div>
                                <dt class="text-sm text-slate-500">Адрес</dt>
                                <dd class="mt-1 text-slate-800">{{ $settings->address }}</dd>
                            </div>
                        @endif
                        @if($settings->working_hours)
                            <div>
                                <dt class="text-sm text-slate-500">Часы работы</dt>
                                <dd class="mt-1 text-slate-800">{{ $settings->working_hours }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if($settings->company_legal_name)
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-6 text-sm text-slate-700">
                        <h2 class="font-semibold text-slate-900 mb-3">Реквизиты компании</h2>
                        <dl class="space-y-1">
                            <div class="flex gap-2"><dt class="text-slate-500 shrink-0">Название:</dt><dd>{{ $settings->company_legal_name }}</dd></div>
                            @if($settings->company_bin)
                                <div class="flex gap-2"><dt class="text-slate-500 shrink-0">БИН:</dt><dd>{{ $settings->company_bin }}</dd></div>
                            @endif
                            @if($settings->company_legal_address)
                                <div class="flex gap-2"><dt class="text-slate-500 shrink-0">Юр.адрес:</dt><dd>{{ $settings->company_legal_address }}</dd></div>
                            @endif
                        </dl>
                    </div>
                @endif

                @if($settings->map_lat && $settings->map_lng)
                    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
                        <div x-data="contactsMap({ lat: {{ $settings->map_lat }}, lng: {{ $settings->map_lng }}, label: @js($settings->address ?? '') })"
                             x-init="init()"
                             wire:ignore>
                            <div x-ref="map" class="h-72 w-full bg-slate-100"></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-6">
                <h2 class="font-semibold text-slate-900 mb-4">Оставить заявку</h2>

                @if(session('contact.sent'))
                    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded text-emerald-800">
                        {{ session('contact.sent') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contacts.store') }}" class="space-y-4">
                    @csrf

                    @if($product)
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <div class="bg-brand-50 border border-brand-200 rounded p-3 text-sm">
                            Запрос по товару: <strong>{{ $product->name }}</strong>
                        </div>
                    @endif

                    <x-input name="name" label="Ваше имя" required maxlength="120" autocomplete="name" />
                    <x-input name="phone" type="tel" label="Телефон" required placeholder="+7XXXXXXXXXX" autocomplete="tel" />
                    <x-input name="email" type="email" label="Email" maxlength="120" autocomplete="email"
                             help="Чтобы выслать вам коммерческое предложение (опционально)." />
                    <x-textarea name="message" label="Сообщение" rows="4" maxlength="2000"
                                help="Опишите ваш запрос или количество.">{{ $product ? 'Прошу выслать цену и условия по '.$product->name : '' }}</x-textarea>

                    <x-button type="submit" variant="primary" size="lg" class="w-full">
                        Отправить заявку
                    </x-button>

                    <p class="text-xs text-slate-500 text-center">
                        Отправляя форму, вы соглашаетесь с обработкой персональных данных.
                    </p>
                </form>
            </div>
        </div>
    </div>
@endsection
