{{--
    Placeholder home — Phase 2.1 ships only the layout/header/footer
    foundation. Real hero / featured products / featured articles arrive
    in Phase 2.2 (HomeController). Keeping this file in the same shape
    as the eventual production version (extends layouts.app + content
    section) means the smoke test we write today keeps working through
    the upgrade.
--}}
@extends('layouts.app', [
    'meta_title' => 'ТРИ АД Construction — ЖБИ в Алматы',
    'meta_description' => 'Производство и продажа железобетонных изделий: '
        .'бетонные кольца, плиты перекрытия, ФБС, опорные подушки. Доставка по Казахстану.',
])

@section('content')
    <section class="bg-slate-50 border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="max-w-3xl">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-semibold text-slate-900 leading-tight">
                    Железобетонные изделия в&nbsp;Алматы
                </h1>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                    Кольца, плиты, ФБС, опорные подушки. Полное соответствие ГОСТ.
                    Доставка по Казахстану.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <x-button :href="url('/catalog')" variant="primary" size="lg">
                        Перейти в каталог
                    </x-button>
                    <x-button :href="url('/contacts')" variant="outline" size="lg">
                        Связаться с нами
                    </x-button>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <p class="text-slate-500 italic">
            Каталог категорий, рекомендованные товары и блог появятся в Phase 2.2.
        </p>
    </section>
@endsection
