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
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[
            ['label' => 'Корзина', 'url' => url('/cart')],
            ['label' => 'Оформление', 'url' => route('checkout.show')],
        ]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">Оформление заказа</h1>

        <form method="POST" action="{{ route('checkout.store') }}"
              x-data="{
                  type: '{{ $oldType }}',
                  delivery: '{{ $oldDelivery }}',
                  payment: '{{ $oldPayment }}',
              }"
              class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf

            <div class="lg:col-span-2 space-y-8">

                <fieldset class="bg-white border border-slate-200 rounded-lg p-6">
                    <legend class="font-semibold text-slate-900 px-2 -ml-2">Тип покупателя</legend>
                    <div class="mt-3 flex flex-col sm:flex-row gap-3">
                        @foreach(CustomerType::cases() as $case)
                            <label class="flex-1 flex items-center gap-3 p-3 border border-slate-300 rounded cursor-pointer hover:border-brand-400"
                                   :class="type === '{{ $case->value }}' ? 'border-brand-500 bg-brand-50' : ''">
                                <input type="radio" name="customer_type" value="{{ $case->value }}"
                                       x-model="type"
                                       @change="payment = '{{ $case->value }}' === 'legal' ? 'bank_transfer' : 'cash'"
                                       class="size-4 text-brand-600 focus:ring-brand-500">
                                <span class="font-medium text-slate-800">{{ $case->getLabel() }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('customer_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
                    <legend class="font-semibold text-slate-900 px-2 -ml-2">Контактные данные</legend>

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
                </fieldset>

                <fieldset class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
                    <legend class="font-semibold text-slate-900 px-2 -ml-2">Доставка</legend>
                    <div class="flex flex-col sm:flex-row gap-3">
                        @foreach(DeliveryMethod::cases() as $case)
                            <label class="flex-1 flex items-center gap-3 p-3 border border-slate-300 rounded cursor-pointer hover:border-brand-400"
                                   :class="delivery === '{{ $case->value }}' ? 'border-brand-500 bg-brand-50' : ''">
                                <input type="radio" name="delivery_method" value="{{ $case->value }}"
                                       x-model="delivery"
                                       class="size-4 text-brand-600 focus:ring-brand-500">
                                <span class="font-medium text-slate-800">{{ $case->getLabel() }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('delivery_method')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div x-show="delivery === '{{ DeliveryMethod::Delivery->value }}'"
                         x-transition.opacity
                         style="display: none;">
                        <x-textarea name="delivery_address" label="Адрес доставки" rows="2" required
                                    help="Полный адрес: город, улица, дом, корпус." />
                    </div>
                </fieldset>

                <fieldset class="bg-white border border-slate-200 rounded-lg p-6 space-y-4">
                    <legend class="font-semibold text-slate-900 px-2 -ml-2">Оплата</legend>
                    <div class="flex flex-col sm:flex-row gap-3">
                        @foreach(PaymentMethod::cases() as $case)
                            <label class="flex-1 flex items-center gap-3 p-3 border border-slate-300 rounded cursor-pointer hover:border-brand-400"
                                   :class="payment === '{{ $case->value }}' ? 'border-brand-500 bg-brand-50' : ''">
                                <input type="radio" name="payment_method" value="{{ $case->value }}"
                                       x-model="payment"
                                       class="size-4 text-brand-600 focus:ring-brand-500">
                                <span class="font-medium text-slate-800">{{ $case->getLabel() }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-slate-500">
                        При безналичном расчёте на вашу почту придёт счёт на оплату по реквизитам.
                    </p>
                </fieldset>

                <fieldset class="bg-white border border-slate-200 rounded-lg p-6">
                    <legend class="font-semibold text-slate-900 px-2 -ml-2">Комментарий</legend>
                    <x-textarea name="comment" rows="3" help="Любые уточнения по заказу (опционально)." />
                </fieldset>
            </div>

            {{-- Order summary sidebar. --}}
            <aside class="bg-slate-50 border border-slate-200 rounded-lg p-6 h-fit lg:sticky lg:top-24">
                <h2 class="font-semibold text-slate-900 mb-4">Ваш заказ</h2>
                <ul class="space-y-3 text-sm border-b border-slate-200 pb-4">
                    @foreach($items as $item)
                        @php($product = $item->product())
                        <li class="flex justify-between gap-3">
                            <span class="text-slate-700">
                                {{ $product?->name ?? 'Товар' }}
                                <span class="text-slate-500">× {{ $item->qty }}</span>
                            </span>
                            <span class="text-slate-900 font-medium whitespace-nowrap">
                                {{ Number::format((float) $item->lineTotal(), locale: 'ru') }} ₸
                            </span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex justify-between">
                    <span class="font-semibold text-slate-900">Итого</span>
                    <span class="text-xl font-semibold text-slate-900">
                        {{ Number::format((float) $cart->subtotal(), locale: 'ru') }} ₸
                    </span>
                </div>

                <x-button type="submit" variant="primary" size="lg" class="w-full mt-6">
                    Оформить заказ
                </x-button>

                <p class="mt-3 text-xs text-slate-500 text-center">
                    Нажимая «Оформить заказ», вы соглашаетесь с обработкой персональных данных.
                </p>

                <a href="{{ route('cart.show') }}" class="block mt-3 text-sm text-center text-slate-500 hover:text-slate-700">
                    ← Вернуться в корзину
                </a>
            </aside>
        </form>
    </div>
@endsection
