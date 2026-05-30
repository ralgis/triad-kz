@php
    use App\Models\Setting;
    $settings ??= Setting::current();

    // Publisher-only attribution: author == publisher == Organization (see
    // docs/blog/PLAN.md §3.1 for rationale — B2B-ЖБИ is not YMYL, fake
    // person bylines carry Spam Policies risk we don't want).
    $orgId = url('/').'/#organization';

    // Three image ratios for Article structured-data image[] (Google's
    // recommendation for rich results coverage across Top Stories /
    // Discover / Search layouts). Fall back to whichever conversion
    // exists so older articles uploaded before the schema_* conversions
    // landed still emit at least one image.
    $coverImages = array_values(array_filter([
        $article->getFirstMediaUrl('cover', 'schema_1_1'),
        $article->getFirstMediaUrl('cover', 'schema_4_3'),
        $article->getFirstMediaUrl('cover', 'schema_16_9'),
    ]));
    if ($coverImages === []) {
        $fallback = $article->getFirstMediaUrl('cover', 'og') ?: $article->getFirstMediaUrl('cover');
        if ($fallback) {
            $coverImages = [$fallback];
        }
    }

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        '@id' => $article->url().'#article',
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $article->url().'#webpage',
        ],
        'headline' => $article->title,
        'alternativeHeadline' => $article->subtitle ?: null,
        'description' => $article->meta_description ?: $article->excerpt ?: null,
        'image' => $coverImages ?: null,
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->effectiveModifiedAt()?->toIso8601String(),
        'author' => ['@id' => $orgId],
        'publisher' => ['@id' => $orgId],
        'wordCount' => $article->word_count ?: null,
        'articleSection' => $article->blogCategory?->name,
        // Hand-curated override hook for special cases (e.g. about[]
        // pointing at specific Products / ГОСТы before the M2M relations
        // land in Phase 2). Admin sets structured_data_override in the
        // Article SEO section.
        ...($article->structured_data_override ?? []),
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
