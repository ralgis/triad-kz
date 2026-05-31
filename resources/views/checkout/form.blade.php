@extends('layouts.app', [
    'meta_title' => 'Оформление заказа — ТРИ АД Construction',
    'noindex' => true,
])

@php
    use App\Enums\CustomerType;
    use App\Enums\DeliveryMethod;
    use App\Enums\PaymentMethod;
    use Illuminate\Support\Number;

    $items = $cart->items();
    $oldType = old('customer_type', CustomerType::Individual->value);
    $oldDelivery = old('delivery_method', DeliveryMethod::Pickup->value);
    $oldPayment = old('payment_method',
        $oldType === CustomerType::Legal->value
            ? PaymentMethod::BankTransfer->value
            : PaymentMethod::Cash->value);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[
            ['label' => 'Корзина', 'url' => url('/cart')],
            ['label' => 'Оформление', 'url' => route('checkout.show')],
        ]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ ЭТАП 2 · ОФОРМЛЕНИЕ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">Оформление заказа</h1>
        </header>

        <form method="POST" action="{{ route('checkout.store') }}"
              x-data="{
                  type: '{{ $oldType }}',
                  delivery: '{{ $oldDelivery }}',
                  payment: '{{ $oldPayment }}',
              }"
              class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
            @csrf

            <div class="lg:col-span-2 space-y-6">

                <fieldset class="bg-document border-2 border-edge">
                    <legend class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm ml-4 -mt-3">Тип покупателя</legend>
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            @foreach(CustomerType::cases() as $case)
                                <label class="flex-1 flex items-center gap-3 px-4 py-3 bg-concrete border-2 border-edge cursor-pointer transition"
                                       :class="type === '{{ $case->value }}' ? 'border-blueprint-600 bg-blueprint-50' : 'hover:border-blueprint-600'">
                                    <input type="radio" name="customer_type" value="{{ $case->value }}"
                                           x-model="type"
                                           @change="payment = '{{ $case->value }}' === 'legal' ? 'bank_transfer' : 'cash'"
                                           class="size-4 text-blueprint-600 focus:ring-blueprint-600 focus:ring-offset-0">
                                    <span class="font-display uppercase tracking-tight text-sm text-steel">{{ $case->getLabel() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('customer_type')
                            <p class="mt-3 font-mono text-[10px] uppercase tracking-wider text-stamp-700">⊘ {{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                <fieldset class="bg-document border-2 border-edge">
                    <legend class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm ml-4 -mt-3">Контактные данные</legend>
                    <div class="p-5 sm:p-6 space-y-4">
                        <x-input name="customer_name" label="ФИО" required maxlength="120" autocomplete="name" />
                        <x-input name="customer_email" type="email" label="Email" required maxlength="120" autocomplete="email" />
                        <x-input name="customer_phone" type="tel" label="Телефон" required placeholder="+7XXXXXXXXXX" autocomplete="tel" />

                        <div x-show="type === '{{ CustomerType::Legal->value }}'"
                             x-transition.opacity
                             class="space-y-4"
                             style="display: none;">
                            <x-input name="customer_company_name" label="Название организации" maxlength="200" autocomplete="organization" />
                            <x-input name="customer_bin" label="БИН" maxlength="12" inputmode="numeric"
                                     help="12 цифр. Для юридических лиц обязательно." />
                        </div>

                        <x-input name="customer_address" label="Адрес плательщика" maxlength="300"
                                 help="Адрес проживания / юридический адрес. Для доставки укажите ниже." />
                    </div>
                </fieldset>

                <fieldset class="bg-document border-2 border-edge">
                    <legend class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm ml-4 -mt-3">Доставка</legend>
                    <div class="p-5 sm:p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            @foreach(DeliveryMethod::cases() as $case)
                                <label class="flex-1 flex items-center gap-3 px-4 py-3 bg-concrete border-2 border-edge cursor-pointer transition"
                                       :class="delivery === '{{ $case->value }}' ? 'border-blueprint-600 bg-blueprint-50' : 'hover:border-blueprint-600'">
                                    <input type="radio" name="delivery_method" value="{{ $case->value }}"
                                           x-model="delivery"
                                           class="size-4 text-blueprint-600 focus:ring-blueprint-600 focus:ring-offset-0">
                                    <span class="font-display uppercase tracking-tight text-sm text-steel">{{ $case->getLabel() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('delivery_method')
                            <p class="mt-2 font-mono text-[10px] uppercase tracking-wider text-stamp-700">⊘ {{ $message }}</p>
                        @enderror

                        <div x-show="delivery === '{{ DeliveryMethod::Delivery->value }}'"
                             x-transition.opacity
                             style="display: none;">
                            <x-textarea name="delivery_address" label="Адрес доставки" rows="2" required
                                        help="Полный адрес: город, улица, дом, корпус." />
                        </div>
                    </div>
                </fieldset>

                <fieldset class="bg-document border-2 border-edge">
                    <legend class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm ml-4 -mt-3">Оплата</legend>
                    <div class="p-5 sm:p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            @foreach(PaymentMethod::cases() as $case)
                                <label class="flex-1 flex items-center gap-3 px-4 py-3 bg-concrete border-2 border-edge cursor-pointer transition"
                                       :class="payment === '{{ $case->value }}' ? 'border-blueprint-600 bg-blueprint-50' : 'hover:border-blueprint-600'">
                                    <input type="radio" name="payment_method" value="{{ $case->value }}"
                                           x-model="payment"
                                           class="size-4 text-blueprint-600 focus:ring-blueprint-600 focus:ring-offset-0">
                                    <span class="font-display uppercase tracking-tight text-sm text-steel">{{ $case->getLabel() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method')
                            <p class="mt-2 font-mono text-[10px] uppercase tracking-wider text-stamp-700">⊘ {{ $message }}</p>
                        @enderror
                        <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">
                            ━━ При безналичном расчёте счёт придёт на email
                        </p>
                    </div>
                </fieldset>

                <fieldset class="bg-document border-2 border-edge">
                    <legend class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm ml-4 -mt-3">Комментарий</legend>
                    <div class="p-5 sm:p-6">
                        <x-textarea name="comment" rows="3" help="Любые уточнения по заказу (опционально)." />
                    </div>
                </fieldset>
            </div>

            {{-- Order summary sidebar. --}}
            <aside class="bg-document border-2 border-edge h-fit lg:sticky lg:top-24">
                <p class="bg-steel text-document px-4 py-2 font-display uppercase tracking-wider text-xs sm:text-sm">Ваш заказ</p>
                <div class="p-5 sm:p-6">
                    <ul class="space-y-3 text-sm border-b-2 border-concrete-dark pb-4">
                        @foreach($items as $item)
                            @php($product = $item->product())
                            <li class="flex justify-between gap-3">
                                <span class="text-steel-soft leading-snug">
                                    {{ $product?->name ?? 'Товар' }}
                                    <span class="font-mono text-haze">× <span class="spec-value">{{ $item->qty }}</span></span>
                                </span>
                                <span class="font-mono text-steel whitespace-nowrap">
                                    <span class="spec-value">{{ Number::format((float) $item->lineTotal(), locale: 'ru') }}</span> ₸
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex justify-between items-baseline">
                        <span class="font-mono text-[10px] text-haze uppercase tracking-wider">━━ Итого</span>
                        <span class="font-display uppercase tracking-tight text-2xl text-steel">
                            <span class="spec-value">{{ Number::format((float) $cart->subtotal(), locale: 'ru') }}</span>
                            <span class="font-mono text-sm text-haze normal-case tracking-normal">₸</span>
                        </span>
                    </div>

                    <x-button type="submit" variant="primary" size="lg" class="w-full mt-6">
                        Подтвердить заказ →
                    </x-button>

                    <p class="mt-3 font-mono text-[9px] sm:text-[10px] text-haze uppercase tracking-wider text-center leading-snug">
                        Нажимая «Подтвердить заказ», вы соглашаетесь с обработкой персональных данных
                    </p>

                    <a href="{{ route('cart.show') }}" class="block mt-4 font-mono text-[10px] text-center text-haze hover:text-blueprint-600 uppercase tracking-wider transition">
                        ← Вернуться в корзину
                    </a>
                </div>
            </aside>
        </form>
    </div>
@endsection
