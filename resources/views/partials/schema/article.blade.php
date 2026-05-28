@php
    use App\Models\Setting;
    $settings ??= Setting::current();

    $image = $article->getFirstMediaUrl('cover', 'og');

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => $article->meta_description ?: $article->excerpt ?: null,
        'image' => $image ?: null,
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at?->toIso8601String(),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $article->url(),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $settings->site_name,
            'logo' => $settings->getFirstMediaUrl('logo') ? [
                '@type' => 'ImageObject',
                'url' => $settings->getFirstMediaUrl('logo'),
            ] : null,
        ],
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
