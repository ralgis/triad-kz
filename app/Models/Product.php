<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Traits\HasSeo;
use App\Traits\HasSlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia, HasPublicUrl
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
        'sku',
        'gost',
        'dimensions',
        'weight_kg',
        'price',
        'price_unit',
        'price_visible',
        'unit_for_order',
        'description',
        'published',
        'featured',
        'in_stock',
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
            'dimensions' => 'array',
            'weight_kg' => 'decimal:2',
            'price' => 'decimal:2',
            'price_visible' => 'boolean',
            'published' => 'boolean',
            'featured' => 'boolean',
            'in_stock' => 'boolean',
        ], $this->seoCasts());
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100)
            ->doNotGenerateSlugsOnUpdate();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function contactSubmissions(): HasMany
    {
        return $this->hasMany(ContactSubmission::class);
    }

    public function registerMediaCollections(): void
    {
        // Чертёж: схема с размерами. Single image.
        $this->addMediaCollection('blueprint')->singleFile();

        // Реальное фото изделия. Single image.
        $this->addMediaCollection('real')->singleFile();

        // Дополнительные фото для галереи (multiple).
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Catalog card thumb on mobile.
        $this->addMediaConversion('thumb')->width(300)->height(300);

        // Catalog card on tablet+desktop.
        $this->addMediaConversion('card')->width(600)->height(600);

        // OG image for social previews + Schema.org Product.image.
        $this->addMediaConversion('og')->width(1200)->height(630);

        // Responsive srcset on product detail page.
        $this->addMediaConversion('mobile')->width(768);
    }

    /**
     * Detail URL — needs a primary category for nesting.
     * If no category, fall back to /catalog/{slug} (no nest).
     *
     * Trailing slash deliberately omitted — see Category::url().
     */
    public function url(?Category $category = null): string
    {
        $category ??= $this->categories->first();

        if ($category) {
            return url('/catalog/'.$category->slug.'/'.$this->slug);
        }

        return url('/catalog/'.$this->slug);
    }

    // ---- Scopes ----

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('published', true);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('featured', true);
    }
}
