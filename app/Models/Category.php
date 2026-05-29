<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Traits\HasSeo;
use App\Traits\HasSlugRedirect;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model implements HasMedia, HasPublicUrl
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
        'parent_id',
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
            ->doNotGenerateSlugsOnUpdate(); // mid-life slug changes go through admin + auto-redirect observer
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function registerMediaCollections(): void
    {
        // Hero image for the category page. Single.
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // nonOptimized() — see Product::registerMediaConversions() for context.
        $this->addMediaConversion('thumb')->width(300)->height(300)->nonOptimized();
        $this->addMediaConversion('card')->width(600)->height(600)->nonOptimized();
        $this->addMediaConversion('og')->width(1200)->height(630)->nonOptimized();
    }

    /**
     * SEO alt for the category cover image. Includes «в Алматы» for
     * local pack relevance — categories are the second-most-linked
     * pages after the homepage, so their image alt carries weight.
     */
    public function imageAlt(): string
    {
        return $this->name.' в Алматы — каталог ЖБИ | завод ТРИ АД';
    }

    /**
     * Hover tooltip — just the category name, no marketing tail.
     */
    public function imageTitle(): string
    {
        return $this->name;
    }

    /**
     * Tree-level URL: /catalog/{slug}.
     *
     * Trailing slash deliberately omitted — Laravel's url() helper strips
     * it anyway, and we want our stored Redirect rows to use the
     * canonical no-trailing-slash form.
     */
    public function url(): string
    {
        return url('/catalog/'.$this->slug);
    }
}
