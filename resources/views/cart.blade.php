@extends('layouts.app', [
    'meta_title' => 'Корзина — ТРИ АД Construction',
    'noindex' => true,
])

@php
    use Illuminate\Support\Number;
    $items = $cart->items();
@endphp

@section('content')
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
        <x-breadcrumb :items="[['label' => 'Корзина', 'url' => url('/cart')]]" />

        <header class="mt-6 sm:mt-8 pb-6 border-b-2 border-edge">
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-3">
                ━━━━━━━ ЗАКАЗ В РАБОТЕ
            </p>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl uppercase">Корзина</h1>
        </header>

        @if(session('cart.empty'))
            <p class="mt-6 px-4 py-3 border-2 border-stamp-600 bg-stamp-50 font-mono text-[10px] uppercase tracking-wider text-stamp-700">
                ⊘ {{ session('cart.empty') }}
            </p>
        @endif

        @if(empty($items))
            <div class="mt-8 p-10 sm:p-16 bg-document border-2 border-edge text-center">
                <p class="font-mono text-sm text-haze uppercase tracking-wider">
                    ⊘ В корзине пока ничего нет
                </p>
                <x-button :href="url('/catalog')" variant="primary" size="lg" class="mt-6">
                    Перейти в каталог →
                </x-button>
            </div>
        @else
            <div class="mt-8 space-y-3">
                @foreach($items as $item)
                    @php
                        $product = $item->product();
                        $image = $product?->getFirstMediaUrl('images', 'thumb');
                    @endphp
                    <div class="flex flex-col sm:flex-row gap-4 p-4 bg-document border-2 border-edge">
                        <div class="size-24 shrink-0 bg-concrete-dark border-2 border-edge overflow-hidden">
                            @if($image)
                                <img src="{{ $image }}" alt="" class="size-full object-contain">
                            @else
                                <div class="size-full flex items-center justify-center font-mono text-[9px] text-haze uppercase tracking-wider">фото</div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            @if($product)
                                @if($product->sku)
                                    <p class="font-display uppercase tracking-tight text-lg sm:text-xl text-steel">
                                        <a href="{{ $product->url() }}" class="hover:text-blueprint-600 transition">{{ $product->sku }}</a>
                                    </p>
                                @endif
                                <p class="text-sm text-steel-soft mt-1 leading-snug">{{ $product->name }}</p>
                            @else
                                <p class="font-mono text-sm text-haze uppercase tracking-wider">⊘ Товар недоступен</p>
                            @endif

                            <p class="mt-2 font-mono text-xs text-haze uppercase tracking-wider">
                                <span class="spec-value text-steel">{{ Number::format((float) $item->unitPrice, locale: 'ru') }}</span> ₸ / {{ $item->unit }}
                            </p>
                        </div>

                        <div class="flex sm:flex-col sm:items-end gap-3 sm:gap-3 justify-between">
                            <form action="{{ route('cart.update', ['productId' => $item->productId]) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="qty" value="{{ $item->qty }}" min="0" max="999"
                                       class="w-20 bg-concrete border-2 border-edge px-2 py-1 font-mono spec-value text-sm text-steel focus:border-blueprint-600 focus:outline-none"
                                       aria-label="Количество">
                                <x-button type="submit" variant="outline" size="sm">Обновить</x-button>
                            </form>

                            <p class="font-mono text-base sm:text-lg text-steel whitespace-nowrap">
                                <span class="spec-value">{{ Number::format((float) $item->lineTotal(), locale: 'ru') }}</span> ₸
                            </p>

                            <form action="{{ route('cart.remove', ['productId' => $item->productId]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-mono text-[10px] text-stamp-700 hover:text-stamp-600 uppercase tracking-wider transition">⊘ Удалить</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center bg-steel text-document p-6 border-2 border-edge">
                <div>
                    <p class="font-mono text-[10px] text-document/60 uppercase tracking-wider">━━ Итого к оплате</p>
                    <p class="mt-1 font-display uppercase tracking-tight text-3xl sm:text-4xl leading-none">
                        <span class="spec-value">{{ Number::format((float) $cart->subtotal(), locale: 'ru') }}</span>
                        <span class="font-mono text-base text-document/70 normal-case tracking-normal">₸</span>
                    </p>
                </div>
                <x-button :href="route('checkout.show')" variant="primary" size="lg">
                    Оформить заказ →
                </x-button>
            </div>
        @endif
    </div>
@endsection
