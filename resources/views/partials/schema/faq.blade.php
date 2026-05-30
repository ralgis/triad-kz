@php
    // $faq shape: [['question' => '...', 'answer' => '...'], ...]
    // Caller must verify $faq non-empty before include — we don't gate
    // here so the partial stays a single-purpose emitter.

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(
            fn (array $item) => [
                '@type' => 'Question',
                'name' => $item['question'] ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'] ?? '',
                ],
            ],
            array_values($faq),
        ),
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
