@php
    use App\Models\Setting;
    $settings ??= Setting::current();

    // $articles is the paginated set the controller passed to the
    // category view. We render only the current-page slice into mainEntity
    // — Google doesn't expect every page's worth of items inlined, and
    // serializing 100s of articles into JSON-LD would explode the response.
    $items = collect($articles->items())
        ->values()
        ->map(fn ($a, int $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => $a->url(),
            'name' => $a->title,
        ])
        ->all();

    $description = $category->description
        ? \Illuminate\Support\Str::limit(trim(strip_tags($category->description)), 280)
        : ($category->meta_description ?: null);

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        '@id' => $category->url().'#collection',
        'name' => $category->name,
        'description' => $description,
        'url' => $category->url(),
        'isPartOf' => ['@id' => url('/').'/#organization'],
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => $articles->total(),
            'itemListElement' => $items,
        ],
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
