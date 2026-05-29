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
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Standards reference («ГОСТы и Серии»).
 *
 * One row per state-standard / engineering-series record. Linked to
 * products via the `gost_product` pivot (M2M). The public /gosts/ page
 * lists all rows as an accordion; product cards link the ГОСТ badge
 * back to its row anchor.
 *
 * `kind` differentiates ГОСТ from Серия — they're conceptually
 * different objects (legal standard vs. engineering catalog) but the
 * admin UI and storage shape is identical, so we keep them in one
 * table with a discriminator.
 */
class Gost extends Model implements HasPublicUrl
{
    use HasFactory;
    use HasSeo;
    use HasSlug;
    use HasSlugRedirect;
    use SoftDeletes;

    public const KIND_GOST = 'gost';

    public const KIND_SERIYA = 'seriya';

    protected $fillable = [
        'kind',
        'label',
        'code',
        'slug',
        'description',
        'sort_order',
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
            'sort_order' => 'integer',
        ], $this->seoCasts());
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('label')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(120)
            ->doNotGenerateSlugsOnUpdate();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Public URL of the reference entry. /gosts/#{slug} anchors into the
     * accordion list page (single-source-of-truth design — every gost
     * lives on one page, not separate detail pages).
     */
    public function url(): string
    {
        return url('/gosts').'#'.$this->slug;
    }

    public function kindLabel(): string
    {
        return $this->kind === self::KIND_GOST ? 'ГОСТ' : 'Серия';
    }

    // ---- Scopes ----

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('label');
    }

    public function scopeGosts(Builder $q): Builder
    {
        return $q->where('kind', self::KIND_GOST);
    }

    public function scopeSeriyas(Builder $q): Builder
    {
        return $q->where('kind', self::KIND_SERIYA);
    }
}
