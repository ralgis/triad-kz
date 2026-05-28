@props(['product'])

@php
    use Illuminate\Support\Number;

    // First category in the M2M is the canonical parent for URLs (mirrors
    // Product::url()). If somehow zero, we fall back to a flat URL — the
    // product is still reachable via internal admin, just not via catalog
    // navigation.
    $primaryCategory = $product->categories->first();
    $url = $primaryCategory
        ? route('catalog.product', ['category' => $primaryCategory->slug, 'product' => $product->slug])
        : url('/catalog/'.$product->slug);

    $image = $product->getFirstMediaUrl('real', 'card')
        ?: $product->getFirstMediaUrl('blueprint', 'card')
        ?: null;
@endphp

<article class="group flex flex-col bg-white border border-slate-200 rounded-lg overflow-hidden hover:border-brand-400 hover:shadow-md transition">
    <a href="{{ $url }}" class="block aspect-square bg-slate-50 overflow-hidden">
        @if($image)
            <img src="{{ $image }}"
                 alt="{{ $product->name }}"
                 loading="lazy"
                 class="w-full h-full object-contain group-hover:scale-105 transition duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-400 text-sm">
                Фото скоро
            </div>
        @endif
    </a>

    <div class="p-4 flex flex-col flex-1">
        @if($product->gost)
            <p class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ $product->gost }}</p>
        @endif

        <h3 class="font-medium text-slate-900 leading-snug mb-2">
            <a href="{{ $url }}" class="hover:text-brand-600">{{ $product->name }}</a>
        </h3>

        <div class="mt-auto pt-3">
            @if($product->price_visible && $product->price !== null)
                <p class="text-lg font-semibold text-slate-900">
                    {{ Number::format((float) $product->price, locale: 'ru') }} ₸
                    @if($product->price_unit)
                        <span class="text-sm font-normal text-slate-500">/ {{ $product->price_unit }}</span>
                    @endif
                </p>
            @else
                <p class="text-sm text-slate-600 italic">Цена по запросу</p>
            @endif

            @if(! $product->in_stock)
                <p class="mt-2 text-xs font-medium text-amber-700 bg-amber-50 inline-block px-2 py-0.5 rounded">
                    Под заказ
                </p>
            @endif
        </div>
    </div>
</article>
