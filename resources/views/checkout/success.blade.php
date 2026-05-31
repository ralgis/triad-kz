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
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="text-center">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ ЭТАП 3 · ПРИНЯТО
            </p>
            <div class="mx-auto w-20 h-20 bg-blueprint-600 text-document flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </div>
            <h1 class="mt-6 text-3xl sm:text-4xl lg:text-5xl uppercase">
                Заказ оформлен
            </h1>
            <p class="mt-3 font-mono text-base sm:text-lg text-steel">
                <span class="text-haze uppercase tracking-wider text-xs">━━ Номер</span>
                <span class="spec-value text-2xl text-steel ml-2">{{ $order->order_number }}</span>
            </p>
        </div>

        <div class="mt-10 bg-document border-2 border-edge">
            <p class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm">Сводка</p>
            <dl class="divide-y-2 divide-concrete-dark">
                <div class="grid grid-cols-2 gap-4 px-4 sm:px-5 py-3 items-baseline">
                    <dt class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">Сумма</dt>
                    <dd class="font-display uppercase tracking-tight text-2xl text-steel text-right">
                        <span class="spec-value">{{ Number::format((float) $order->total, locale: 'ru') }}</span>
                        <span class="font-mono text-sm text-haze normal-case tracking-normal">₸</span>
                    </dd>
                </div>
                <div class="grid grid-cols-2 gap-4 px-4 sm:px-5 py-2.5 items-baseline">
                    <dt class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">Способ оплаты</dt>
                    <dd class="text-sm text-steel text-right">{{ $order->payment_method->getLabel() }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-4 px-4 sm:px-5 py-2.5 items-baseline">
                    <dt class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">Доставка</dt>
                    <dd class="text-sm text-steel text-right">{{ $order->delivery_method->getLabel() }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-4 px-4 sm:px-5 py-2.5 items-baseline">
                    <dt class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">Статус</dt>
                    <dd class="text-sm">
                        <span class="font-mono text-[10px] uppercase tracking-wider px-2 py-1 border-2 border-blueprint-600 bg-blueprint-50 text-blueprint-700">
                            {{ $order->status->getLabel() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        @if($isBank)
            <div class="mt-6 bg-blueprint-50 border-2 border-blueprint-600 p-5 sm:p-6">
                <p class="font-mono text-[10px] sm:text-xs uppercase tracking-wider text-blueprint-700">━━ Что дальше</p>
                <p class="mt-2 text-steel leading-relaxed">
                    На указанную почту <strong class="font-semibold">{{ $order->customer_email }}</strong> отправлено
                    письмо с подтверждением заказа. К письму приложен счёт на оплату по реквизитам.
                </p>
                @if($order->invoice_pdf_path)
                    <x-button :href="route('order.invoice', ['order' => $order->order_number])"
                              variant="primary"
                              size="md"
                              class="mt-4">
                        ⬇ Скачать счёт PDF
                    </x-button>
                @endif
            </div>
        @else
            <div class="mt-6 bg-blueprint-50 border-2 border-blueprint-600 p-5 sm:p-6">
                <p class="font-mono text-[10px] sm:text-xs uppercase tracking-wider text-blueprint-700">━━ Что дальше</p>
                <p class="mt-2 text-steel leading-relaxed">
                    Менеджер свяжется по телефону <strong class="font-mono spec-value">{{ $order->customer_phone }}</strong>
                    для подтверждения заказа и согласования времени получения.
                </p>
            </div>
        @endif

        <div class="mt-10 text-center">
            <x-button :href="url('/catalog')" variant="outline" size="lg">
                ← Продолжить покупки
            </x-button>
        </div>
    </div>
@endsection
