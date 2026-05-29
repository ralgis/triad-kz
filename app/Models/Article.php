<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Traits\HasSeo;
use App\Traits\HasSlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'title',
        'slug',
        'excerpt',
        'content',
        'published_at',
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
    }

    public function url(): string
    {
        return url('/blog/'.$this->slug);
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
