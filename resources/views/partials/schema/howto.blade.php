@php
    // $article required — for name/description fallback.
    // $steps shape: [['name' => '...', 'text' => '...', 'image' => '?url'], ...]
    // Caller verifies $steps non-empty; we don't gate here.

    // NB: Google removed HowTo SERP rich results in August 2023. This
    // schema is emitted for AI extraction (Perplexity/ChatGPT) — not
    // for SERP visual enhancements.

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        '@id' => $article->url().'#howto',
        'name' => $article->title,
        'description' => $article->meta_description ?: $article->excerpt ?: null,
        'step' => array_map(
            fn (int $i, array $s) => array_filter([
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name' => $s['name'] ?? null,
                'text' => $s['text'] ?? null,
                'image' => $s['image'] ?? null,
            ]),
            array_keys($steps),
            array_values($steps),
        ),
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
