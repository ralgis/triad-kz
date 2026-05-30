<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Traits\HasSeo;
use App\Traits\HasSlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Blog rubric / topic-cluster hub.
 *
 * Distinct from Category (catalog rubric). Sharing one table for both
 * blog and catalog rubrics couples their slug namespaces and SEO
 * funnels — that's a refactor pain we don't need.
 *
 * `description` is the pillar-style 300-500-word block rendered on
 * /blog/category/{slug}. Acts as the topic-cluster pillar even before
 * an explicit pillar article exists.
 */
class BlogCategory extends Model implements HasMedia, HasPublicUrl
{
    use HasFactory;
    use HasSeo;
    use HasSlug;
    use HasSlugRedirect;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'order',
        'published',
        'listed',
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
            'order' => 'integer',
            'published' => 'boolean',
            'listed' => 'boolean',
        ], $this->seoCasts());
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(80)
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // nonOptimized() — see Product::registerMediaConversions().
        $this->addMediaConversion('thumb')->width(300)->height(200)->nonOptimized();
        $this->addMediaConversion('card')->width(600)->height(400)->nonOptimized();
        $this->addMediaConversion('og')->width(1200)->height(630)->nonOptimized();
        $this->addMediaConversion('hero')->width(1600)->nonOptimized();
    }

    public function url(): string
    {
        return url('/blog/category/'.$this->slug);
    }

    /**
     * Alt describes the IMAGE (the rubric's cover photo), not the page —
     * WCAG/SEO rule: don't reuse page metadata as image alt.
     */
    public function imageAlt(): string
    {
        return 'Иллюстрация рубрики «'.$this->name.'» — блог ТРИ АД';
    }

    public function imageTitle(): string
    {
        return $this->name;
    }

    // ---- Scopes ----

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    public function scopeListed(Builder $q): Builder
    {
        return $q->where('listed', true);
    }
}
