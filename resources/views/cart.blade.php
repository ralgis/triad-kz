@extends('layouts.app', [
    'meta_title' => 'Корзина — ТРИ АД Construction',
    'noindex' => true,
])

@php
    use Illuminate\Support\Number;
    $items = $cart->items();
@endphp

@section('content')
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <x-breadcrumb :items="[['label' => 'Корзина', 'url' => url('/cart')]]" />

        <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-slate-900">Корзина</h1>

        @if(session('cart.empty'))
            <p class="mt-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                {{ session('cart.empty') }}
            </p>
        @endif

        @if(empty($items))
            <div class="mt-8 text-center bg-slate-50 border border-slate-200 rounded-lg py-16">
                <p class="text-lg text-slate-600">В корзине пока ничего нет.</p>
                <x-button :href="url('/catalog')" variant="primary" size="lg" class="mt-6">
                    Перейти в каталог
                </x-button>
            </div>
        @else
            <div class="mt-8 space-y-4">
                @foreach($items as $item)
                    @php
                        $product = $item->product();
                        $image = $product?->getFirstMediaUrl('real', 'thumb')
                            ?: $product?->getFirstMediaUrl('blueprint', 'thumb');
                    @endphp
                    <div class="flex flex-col sm:flex-row gap-4 p-4 bg-white border border-slate-200 rounded-lg">
                        <div class="size-24 shrink-0 bg-slate-50 rounded overflow-hidden">
                            @if($image)
                                <img src="{{ $image }}" alt="" class="size-full object-contain">
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            @if($product)
                                <h3 class="font-medium text-slate-900">
                                    <a href="{{ $product->url() }}" class="hover:text-brand-600">{{ $product->name }}</a>
                                </h3>
                                @if($product->sku)
                                    <p class="text-sm text-slate-500 mt-1">Артикул: {{ $product->sku }}</p>
                                @endif
                            @else
                                <h3 class="font-medium text-slate-400 italic">Товар недоступен</h3>
                            @endif

                            <p class="text-sm text-slate-700 mt-2">
                                {{ Number::format((float) $item->unitPrice, locale: 'ru') }} ₸ / {{ $item->unit }}
                            </p>
                        </div>

                        <div class="flex sm:flex-col sm:items-end gap-3 sm:gap-2 justify-between">
                            <form action="{{ route('cart.update', ['productId' => $item->productId]) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="qty" value="{{ $item->qty }}" min="0" max="999"
                                       class="w-20 rounded border-slate-300 px-2 py-1 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none"
                                       aria-label="Количество">
                                <x-button type="submit" variant="outline" size="sm">Обновить</x-button>
                            </form>

                            <p class="font-semibold text-slate-900 whitespace-nowrap">
                                {{ Number::format((float) $item->lineTotal(), locale: 'ru') }} ₸
                            </p>

                            <form action="{{ route('cart.remove', ['productId' => $item->productId]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:text-red-700">Удалить</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center bg-slate-50 border border-slate-200 rounded-lg p-6">
                <div>
                    <p class="text-sm text-slate-500">Итого</p>
                    <p class="text-3xl font-semibold text-slate-900">
                        {{ Number::format((float) $cart->subtotal(), locale: 'ru') }} ₸
                    </p>
                </div>
                <x-button :href="route('checkout.show')" variant="primary" size="lg">
                    Оформить заказ
                </x-button>
            </div>
        @endif
    </div>
@endsection
