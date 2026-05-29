@php
    // All product images in admin-defined order — Google Rich Results
    // is happy with a single-URL string or array; we give it the array
    // for richer carousels in SERP.
    $images = $product->getMedia('images')
        ->map(fn ($m) => $m->getUrl('og'))
        ->filter()
        ->values()
        ->all();

    // Schema.org PropertyValue blocks let Google rank the product for
    // dimension queries («КС10.6 высота 590 мм») and may surface the
    // spec table in Rich Results. specRows() is the single source of
    // truth — same data the HTML <dl> renders.
    $additionalProperty = array_map(fn (array $r) => array_filter([
        '@type' => 'PropertyValue',
        'name' => $r['label'],
        'value' => (string) $r['value'],
        'unitText' => $r['unit'] ?: null,
    ]), $product->specRows());

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku ?: null,
        'gtin' => null,
        'description' => $product->meta_description
            ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 300),
        'image' => $images ?: null,
        'url' => $product->url($category ?? null),
        'additionalProperty' => $additionalProperty ?: null,
        'weight' => $product->weight_kg ? [
            '@type' => 'QuantitativeValue',
            'value' => (string) $product->weight_kg,
            'unitCode' => 'KGM',
        ] : null,
        'offers' => ($product->price_visible && $product->price !== null) ? [
            '@type' => 'Offer',
            'priceCurrency' => 'KZT',
            'price' => (string) $product->price,
            'availability' => $product->in_stock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/PreOrder',
            'url' => $product->url($category ?? null),
        ] : null,
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
