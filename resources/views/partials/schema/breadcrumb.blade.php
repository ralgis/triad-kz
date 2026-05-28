@php
    // $items shape: [['label' => '...', 'url' => '...'], ...] — same as
    // the visual <x-breadcrumb> component. Position is 1-indexed
    // starting from "Главная" which is implicit in the visual list.
    $list = [
        ['name' => 'Главная', 'item' => url('/')],
        ...array_map(fn ($i) => ['name' => $i['label'], 'item' => $i['url']], $items),
    ];

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(
            fn (int $idx, array $row) => [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'name' => $row['name'],
                'item' => $row['item'],
            ],
            array_keys($list),
            $list,
        ),
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
