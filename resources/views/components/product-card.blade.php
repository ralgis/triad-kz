@props(['product'])

@php
    use Illuminate\Support\Number;

    // First category in the M2M is the canonical parent for URLs (mirrors
    // Product::url()). If somehow zero, we fall back to a flat URL.
    $primaryCategory = $product->categories->first();
    $url = $primaryCategory
        ? route('catalog.product', ['category' => $primaryCategory->slug, 'product' => $product->slug])
        : url('/catalog/'.$product->slug);

    $image = $product->getFirstMediaUrl('images', 'card') ?: null;

    // First ГОСТ becomes the stamp ribbon on the photo — single one is
    // enough; the detail page shows the full list.
    $firstGost = $product->relationLoaded('gosts')
        ? $product->gosts->first()
        : $product->gosts()->first();

    $freshness = $product->priceFreshnessLabel();
@endphp

<article class="group flex flex-col bg-document border-2 border-edge hover:translate-y-[-2px] transition">
    {{-- Square photo with ГОСТ-stamp ribbon overlay --}}
    <a href="{{ $url }}" class="block aspect-square bg-concrete-dark border-b-2 border-edge overflow-hidden relative">
        @if($image)
            <img src="{{ $image }}"
                 alt="{{ $product->imageAlt(0) }}"
                 title="{{ $product->imageTitle() }}"
                 loading="lazy"
                 class="w-full h-full object-contain group-hover:scale-105 transition duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center font-mono text-xs text-haze uppercase tracking-wider">
                фото скоро
            </div>
        @endif

        @if($firstGost)
            <div class="absolute top-3 right-3 bg-stamp-600 text-document px-2 sm:px-3 py-1 font-mono text-[10px] sm:text-xs uppercase tracking-wider">
                {{ $firstGost->fullLabel() }}
            </div>
        @endif
    </a>

    <div class="p-4 sm:p-5 flex flex-col flex-1">
        {{-- SKU as the load-bearing display element. Product name as
             subtitle below — that's the «catalog index card» reading
             order. --}}
        @if($product->sku)
            <p class="font-display text-xl sm:text-2xl uppercase tracking-tight text-steel">
                <a href="{{ $url }}" class="hover:text-blueprint-600 transition">{{ $product->sku }}</a>
            </p>
        @endif
        <h3 class="text-sm text-steel-soft mt-1 leading-snug">
            {{ $product->name }}
        </h3>

        {{-- Spec rows in mono — picks the most informative 2-3 dims from
             the model's specRows(). Skips Material/Material-grade rows
             because the card already crowded; full table on detail page. --}}
        @php
            $specRows = collect($product->specRows())
                ->filter(fn ($r) => in_array($r['key'], [
                    'length_mm', 'width_mm', 'height_mm',
                    'inner_diameter_mm', 'outer_diameter_mm', 'plate_diameter_mm',
                    'weight_t', 'concrete_volume_m3',
                ], true))
                ->take(3);
        @endphp
        @if($specRows->isNotEmpty())
            <dl class="mt-3 space-y-1 font-mono text-xs sm:text-sm">
                @foreach($specRows as $row)
                    <div class="flex justify-between gap-2">
                        <dt class="text-haze truncate">{{ $row['label'] }}</dt>
                        <dd class="spec-value text-steel whitespace-nowrap">
                            {{ $row['value'] }}@if($row['unit']) {{ $row['unit'] }}@endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif

        {{-- Price + cart action + freshness. Always render this block on
             the card so cards stay same height visually. --}}
        <div class="mt-4 pt-3 border-t-2 border-concrete-dark mt-auto">
            <div class="flex items-baseline justify-between gap-2">
                @if($product->price_visible && $product->price !== null)
                    <p class="font-mono text-base sm:text-lg text-steel">
                        <span class="spec-value">{{ Number::format((float) $product->price, locale: 'ru') }}</span>
                        <span class="text-xs text-haze">₸@if($product->price_unit) /{{ $product->price_unit }}@endif</span>
                    </p>
                @else
                    <p class="font-mono text-xs sm:text-sm text-haze uppercase tracking-wider">Цена по запросу</p>
                @endif
                <x-button variant="primary" size="sm" :href="$url">В корзину</x-button>
            </div>

            @if($freshness && $product->price_visible && $product->price !== null)
                <p class="mt-2 font-mono text-[10px] text-haze uppercase tracking-wider flex items-center gap-1.5">
                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12l4.243 4.243L19 6.515"/></svg>
                    Цена актуальна · {{ $freshness }}
                </p>
            @endif

            @if(! $product->in_stock)
                <p class="mt-2 font-mono text-[10px] text-stamp-700 uppercase tracking-wider">⊘ Под заказ</p>
            @endif
        </div>
    </div>
</article>
