<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Enums\ArticleType;
use App\Traits\HasSeo;
use App\Traits\HasSlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Article extends Model implements HasMedia, HasPublicUrl
{
    use HasFactory;
    use HasSeo;
    use HasSlug;
    use HasSlugRedirect;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'blog_category_id',
        'article_type',
        'is_pillar',
        'pillar_id',
        'title',
        'subtitle',
        'slug',
        'excerpt',
        'content',
        'word_count',
        'reading_minutes',
        'published_at',
        'updated_content_at',
        'featured',
        'pinned_until',
        'toc_enabled',
        'faq',
        'how_to_steps',
        'external_sources',
        // SEO
        'meta_title',
        'meta_description',
        'canonical_url',
        'noindex',
        'structured_data_override',
    ];

    protected function casts(): array
    {
        return array_merge([
            'published_at' => 'datetime',
            'updated_content_at' => 'datetime',
            'reading_minutes' => 'integer',
            'word_count' => 'integer',
            'article_type' => ArticleType::class,
            'is_pillar' => 'boolean',
            'featured' => 'boolean',
            'pinned_until' => 'datetime',
            'toc_enabled' => 'boolean',
            'faq' => 'array',
            'how_to_steps' => 'array',
            'external_sources' => 'array',
        ], $this->seoCasts());
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100)
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Sanitize WYSIWYG HTML on save — strips <script>, event handlers,
     * dangerous attributes via HTMLPurifier (config/purifier.php). We
     * sanitize on SAVE not on render so cached content is already safe
     * and we don't double-escape ([Larasec guidance](https://stackshield.io/blog/laravel-xss-protection-guide)).
     *
     * Admin is trusted but: (a) account compromise, (b) future user-
     * submitted content (Phase 3 comments) would otherwise carry XSS.
     */
    public function setContentAttribute(?string $value): void
    {
        $this->attributes['content'] = $value === null || $value === ''
            ? $value
            : clean($value, 'default');
    }

    protected static function booted(): void
    {
        // Recompute reading stats whenever content changes. Cheap (single
        // regex on plain text), so we don't bother gating on isDirty —
        // editing meta_title alone still recomputes the same numbers.
        // updated_content_at is NOT touched here on purpose: it's the
        // human-curated «meaningful update» signal (see effectiveModifiedAt).
        static::saving(function (Article $article): void {
            if ($article->isDirty('content') || $article->word_count === null) {
                $article->recomputeReadingStats();
            }
        });
    }

    /**
     * @return BelongsTo<BlogCategory, $this>
     */
    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    /**
     * The pillar this article is a cluster of. null = either standalone
     * or this article IS the pillar (use is_pillar flag to disambiguate).
     *
     * @return BelongsTo<Article, $this>
     */
    public function pillar(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pillar_id');
    }

    /**
     * All clusters that point at this article as their pillar — only
     * meaningful when is_pillar = true. We don't constrain on the flag
     * in the query (would shadow if data drifts); call sites should
     * gate on is_pillar themselves.
     *
     * @return HasMany<Article, $this>
     */
    public function clusters(): HasMany
    {
        return $this->hasMany(self::class, 'pillar_id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @return BelongsToMany<Gost, $this>
     */
    public function gosts(): BelongsToMany
    {
        return $this->belongsToMany(Gost::class)
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Catalog categories (NOT blog rubrics) — articles can cross-reference
     * product categories for breadcrumb-style cross-linking.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function catalogCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // nonOptimized() — see Product::registerMediaConversions() for context.
        $this->addMediaConversion('thumb')->width(300)->height(300)->nonOptimized();
        $this->addMediaConversion('card')->width(600)->height(400)->nonOptimized();
        $this->addMediaConversion('og')->width(1200)->height(630)->nonOptimized();
        $this->addMediaConversion('hero')->width(1600)->nonOptimized();

        // 3 aspect ratios for Google's Article structured-data image[]
        // requirement (1:1, 4:3, 16:9). Without all three, Article rich
        // results eligibility drops sharply — these are what Top Stories
        // / Discover / AI Overviews actually read.
        $this->addMediaConversion('schema_1_1')->width(1200)->height(1200)->nonOptimized();
        $this->addMediaConversion('schema_4_3')->width(1200)->height(900)->nonOptimized();
        $this->addMediaConversion('schema_16_9')->width(1200)->height(675)->nonOptimized();
    }

    public function url(): string
    {
        return url('/blog/'.$this->slug);
    }

    /**
     * SEO alt for the article cover. Article images are usually
     * illustrative not commercial, so we don't tack on city/brand —
     * the title alone is the right keyword target.
     */
    public function imageAlt(): string
    {
        return $this->title;
    }

    public function imageTitle(): string
    {
        return $this->title;
    }

    /**
     * The freshness signal for Article.dateModified — distinct from
     * Eloquent's updated_at because Google's Helpful Content Update
     * (2023+) penalises fake-touch updates. Falls back to published_at
     * so brand-new articles still emit a valid dateModified.
     */
    public function effectiveModifiedAt(): ?Carbon
    {
        // Larastan's stub for $model->{datetime_cast} narrows to ?string
        // in some passes — we know the datetime cast returns Carbon|null
        // at runtime, so we just assert the type rather than threading
        // assertions through every caller.
        /** @var Carbon|null $modified */
        $modified = $this->updated_content_at;
        if ($modified !== null) {
            return $modified;
        }
        /** @var Carbon|null $published */
        $published = $this->published_at;

        return $published;
    }

    /**
     * Recompute word_count and reading_minutes from the current content.
     * Called by ArticleObserver on save. 180 wpm is the lower bound for
     * technical reading speed — generic blog calculators use 250 wpm
     * which overstates how fast ЖБИ content actually reads.
     */
    /**
     * Pulls out a `[summary]...[/summary]` block from content (case-
     * insensitive, multiline) for the TL;DR box rendered above the
     * cover image. Returns null when no marker is present.
     *
     * The marker is admin-typed — case is preserved for tags but body
     * is taken verbatim. Stripped from the main content render in the
     * view layer.
     */
    public function extractTldr(): ?string
    {
        if (! preg_match('/\[summary\](?P<body>.*?)\[\/summary\]/isu', (string) $this->content, $m)) {
            return null;
        }

        $body = trim(strip_tags($m['body']));

        return $body !== '' ? $body : null;
    }

    public function contentWithoutTldr(): string
    {
        return (string) preg_replace(
            '/\[summary\].*?\[\/summary\]/isu',
            '',
            (string) $this->content,
        );
    }

    public function recomputeReadingStats(): void
    {
        $plain = trim(strip_tags((string) $this->content));
        if ($plain === '') {
            $this->word_count = 0;
            $this->reading_minutes = 0;

            return;
        }

        // Split on whitespace + Unicode punctuation — \b word boundaries
        // behave inconsistently with Cyrillic, whereas \p{P} catches the
        // full punctuation class reliably on Russian text.
        $parts = preg_split('/[\s\p{P}]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $this->word_count = is_array($parts) ? count($parts) : 0;
        $this->reading_minutes = max(1, (int) ceil(($this->word_count ?? 0) / 180));
    }

    /**
     * Siblings in the same blog category — used in the «Также в категории»
     * block on the article detail page. Excludes self and (optionally)
     * an additional exclude list used to dedupe against pillar/cluster
     * recommendations once those land in Phase 2.
     *
     * @param array<int> $exclude
     * @return Collection<int, Article>
     */
    public function relatedInBlogCategory(int $limit = 4, array $exclude = []): Collection
    {
        if ($this->blog_category_id === null) {
            return new Collection;
        }

        return self::query()
            ->published()
            ->where('blog_category_id', $this->blog_category_id)
            ->whereNotIn('id', array_merge($exclude, [$this->id]))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    // ---- Scopes ----

    /**
     * Articles publicly visible: published_at set AND not in future.
     */
    public function scopePublished(Builder $q): Builder
    {
        return $q->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
