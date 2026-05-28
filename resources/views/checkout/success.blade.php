@extends('layouts.app', [
    'meta_title' => 'Заказ оформлен — ТРИ АД Construction',
    'noindex' => true,
])

@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use Illuminate\Support\Number;

    $isBank = $order->payment_method === PaymentMethod::BankTransfer;
@endphp

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="text-center">
            <div class="mx-auto size-16 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </div>
            <h1 class="mt-6 text-3xl sm:text-4xl font-semibold text-slate-900">
                Спасибо! Заказ оформлен.
            </h1>
            <p class="mt-3 text-lg text-slate-600">
                Номер вашего заказа: <span class="font-mono font-semibold text-slate-900">{{ $order->order_number }}</span>
            </p>
        </div>

        <div class="mt-10 bg-white border border-slate-200 rounded-lg p-6 space-y-4">
            <div class="flex justify-between border-b border-slate-200 pb-3">
                <span class="text-slate-500">Сумма</span>
                <span class="text-xl font-semibold text-slate-900">
                    {{ Number::format((float) $order->total, locale: 'ru') }} ₸
                </span>
            </div>

            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Способ оплаты</span>
                <span class="text-slate-900">{{ $order->payment_method->getLabel() }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Доставка</span>
                <span class="text-slate-900">{{ $order->delivery_method->getLabel() }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Статус</span>
                <span class="text-slate-900">{{ $order->status->getLabel() }}</span>
            </div>
        </div>

        @if($isBank)
            <div class="mt-6 bg-brand-50 border border-brand-200 rounded-lg p-6">
                <h2 class="font-semibold text-slate-900">Что дальше</h2>
                <p class="mt-2 text-slate-700">
                    На указанную почту <strong>{{ $order->customer_email }}</strong> отправлено письмо
                    с подтверждением заказа. К письму приложен счёт на оплату по реквизитам.
                </p>
                @if($order->invoice_pdf_path)
                    <x-button :href="route('order.invoice', ['order' => $order->order_number])"
                              variant="primary"
                              size="md"
                              class="mt-4">
                        Скачать счёт PDF
                    </x-button>
                @endif
            </div>
        @else
            <div class="mt-6 bg-brand-50 border border-brand-200 rounded-lg p-6">
                <h2 class="font-semibold text-slate-900">Что дальше</h2>
                <p class="mt-2 text-slate-700">
                    Наш менеджер свяжется с вами по телефону <strong>{{ $order->customer_phone }}</strong>
                    для подтверждения заказа и согласования удобного времени получения.
                </p>
            </div>
        @endif

        <div class="mt-8 text-center">
            <x-button :href="url('/catalog')" variant="outline" size="lg">
                Продолжить покупки
            </x-button>
        </div>
    </div>
@endsection
