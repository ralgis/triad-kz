<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Traits\HasSeo;
use App\Traits\HasSlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Standards reference («ГОСТы и Серии»).
 *
 * Carries both ГОСТ-style standards and the typical Серии (working-
 * drawing catalogs derived from a ГОСТ). The discriminator is `kind`.
 *
 * Schema is dense because the public /gosts/ page should give a B2B
 * buyer the full legal context at a glance — what the standard
 * covers, when it was introduced in Kazakhstan, whether it's still in
 * force, what replaced it if not, and which ГОСТ a series derives
 * from. See the migration file for per-column rationale.
 *
 * `label` holds just the code («8020-90», «3.900.1-14 (выпуск 1)»);
 * the kind-prefixed display string comes from `fullLabel()`.
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
        'title',
        'code',
        'slug',
        'description',
        'sort_order',
        'introduced_at',
        'is_current',
        'effective_in_kz_until',
        'superseded_by_id',
        'superseded_note',
        'relates_to_gost_id',
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
            'is_current' => 'boolean',
            'introduced_at' => 'date',
            'effective_in_kz_until' => 'date',
        ], $this->seoCasts());
    }

    public function getSlugOptions(): SlugOptions
    {
        // Slug = «{kind}-{code or label}» («gost-8020-90»,
        // «seriya-3-900-1-14»). Using `code` when available gives a
        // bare identifier; `label` is the fallback for rows without a
        // numeric code.
        return SlugOptions::create()
            ->generateSlugsFrom(fn (self $g) => $g->kind.' '.($g->code ?: $g->label))
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(120)
            ->doNotGenerateSlugsOnUpdate();
    }

    // ---- Relations ----

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Parent ГОСТ for a Серия (e.g. Серия 3.900.1-14 → ГОСТ 8020-90).
     * Null when the series stands on its own (e.g. Серия 3.006.1-2.87)
     * or for ГОСТ records themselves.
     */
    public function relatesToGost(): BelongsTo
    {
        return $this->belongsTo(self::class, 'relates_to_gost_id');
    }

    /**
     * Reverse of relatesToGost — series derived from this ГОСТ. Useful
     * for the public /gosts/ page so a ГОСТ row can list its working-
     * drawing catalogs inline.
     */
    public function series(): HasMany
    {
        return $this->hasMany(self::class, 'relates_to_gost_id');
    }

    /**
     * Replacement record (e.g. ГОСТ 8020-90 → ГОСТ 8020-2016). Null
     * when the standard is still current or when its replacement isn't
     * tracked in our reference (in which case `superseded_note` may
     * carry the textual successor name).
     */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    /**
     * Reverse — records this one replaced. A new edition may replace
     * multiple historical predecessors in principle.
     */
    public function predecessors(): HasMany
    {
        return $this->hasMany(self::class, 'superseded_by_id');
    }

    // ---- Display helpers ----

    /**
     * Kind-prefixed display name («ГОСТ 8020-90», «Серия 3.900.1-14
     * (выпуск 1)»). Single source of truth for the user-visible label;
     * stored `label` is the bare code without prefix.
     */
    public function fullLabel(): string
    {
        $prefix = $this->kind === self::KIND_GOST ? 'ГОСТ' : 'Серия';

        return $prefix.' '.$this->label;
    }

    public function kindLabel(): string
    {
        return $this->kind === self::KIND_GOST ? 'ГОСТ' : 'Серия';
    }

    /**
     * Public URL of the reference entry. /gosts/#{slug} anchors into
     * the accordion list page — single-source-of-truth design — every
     * gost lives on one page, not separate detail pages.
     */
    public function url(): string
    {
        return url('/gosts').'#'.$this->slug;
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

    public function scopeCurrent(Builder $q): Builder
    {
        return $q->where('is_current', true);
    }
}
