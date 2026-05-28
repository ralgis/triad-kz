@php
    $imageReal = $product->getFirstMediaUrl('real', 'og');
    $imageBlueprint = $product->getFirstMediaUrl('blueprint', 'og');
    $images = array_values(array_filter([$imageReal, $imageBlueprint]));

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
