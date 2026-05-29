<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Traits\HasSeo;
use App\Traits\HasSlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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
        // Geometry — мм
        'length_mm',
        'width_mm',
        'height_mm',
        'thickness_mm',
        'inner_diameter_mm',
        'outer_diameter_mm',
        'plate_diameter_mm',
        'hole_diameter_mm',
        // Material
        'concrete_grade',
        'concrete_volume_m3',
        'steel_kg',
        'weight_t',
        // Welded mesh (категория сетка-сварная)
        'mesh_rod_diameter_mm',
        'mesh_cell_length_mm',
        'mesh_cell_width_mm',
        'price',
        'price_unit',
        'price_visible',
        'unit_for_order',
        'description',
        'published',
        'featured',
        'in_stock',
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
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'thickness_mm' => 'integer',
            'inner_diameter_mm' => 'integer',
            'outer_diameter_mm' => 'integer',
            'plate_diameter_mm' => 'integer',
            'hole_diameter_mm' => 'integer',
            'concrete_volume_m3' => 'decimal:3',
            'steel_kg' => 'decimal:2',
            'weight_t' => 'decimal:3',
            'mesh_rod_diameter_mm' => 'integer',
            'mesh_cell_length_mm' => 'integer',
            'mesh_cell_width_mm' => 'integer',
            'price' => 'decimal:2',
            'price_visible' => 'boolean',
            'published' => 'boolean',
            'featured' => 'boolean',
            'in_stock' => 'boolean',
            'listed' => 'boolean',
        ], $this->seoCasts());
    }

    /**
     * Effective product specs as a (label, value, unit) list, in the
     * display order shown on the catalog detail page. Empty values are
     * filtered out so each product only renders its applicable rows.
     *
     * Used by both the product detail Blade and the Schema.org
     * additionalProperty block so the two stay in lockstep — no
     * duplicate "what to show" logic in two places.
     *
     * @return array<int, array{key: string, label: string, value: int|float|string, unit: string}>
     */
    public function specRows(): array
    {
        $rows = [
            ['length_mm', 'Длина', $this->length_mm, 'мм'],
            ['width_mm', 'Ширина', $this->width_mm, 'мм'],
            ['height_mm', 'Высота', $this->height_mm, 'мм'],
            ['thickness_mm', 'Толщина', $this->thickness_mm, 'мм'],
            ['inner_diameter_mm', 'Внутренний диаметр', $this->inner_diameter_mm, 'мм'],
            ['outer_diameter_mm', 'Внешний диаметр', $this->outer_diameter_mm, 'мм'],
            ['plate_diameter_mm', 'Диаметр плиты', $this->plate_diameter_mm, 'мм'],
            ['hole_diameter_mm', 'Диаметр отверстия', $this->hole_diameter_mm, 'мм'],
            ['concrete_volume_m3', 'Объём бетона', $this->concrete_volume_m3, 'м³'],
            ['concrete_grade', 'Марка бетона', $this->concrete_grade, ''],
            ['weight_t', 'Вес', $this->weight_t, 'т'],
            ['steel_kg', 'Расход стали', $this->steel_kg, 'кг'],
            // Welded mesh — only one or two will be non-null and only
            // for the сетка-сварная category, so they sort to the
            // bottom of the catch-all spec block.
            ['mesh_rod_diameter_mm', 'Диаметр прутка', $this->mesh_rod_diameter_mm, 'мм'],
            ['mesh_cell', 'Размер ячейки', $this->meshCellLabel(), ''],
        ];

        return array_values(array_filter(array_map(
            fn ($r) => ($r[2] !== null && $r[2] !== '' && $r[2] !== 0 && $r[2] !== '0')
                ? ['key' => $r[0], 'label' => $r[1], 'value' => $r[2], 'unit' => $r[3]]
                : null,
            $rows,
        )));
    }

    /**
     * «100×100» / «50×100» when both cell dimensions are present, just
     * the side-length when they're equal-or-only-one-set, or null.
     * Pulled out so specRows() stays a flat (key,label,value,unit)
     * list — mesh size is the one row whose value isn't a single
     * scalar.
     */
    public function meshCellLabel(): ?string
    {
        $l = $this->mesh_cell_length_mm;
        $w = $this->mesh_cell_width_mm;

        if (! $l && ! $w) {
            return null;
        }

        if ($l && $w && $l !== $w) {
            return $l.'×'.$w.' мм';
        }

        return ($l ?: $w).' мм';
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

    public function gosts(): BelongsToMany
    {
        return $this->belongsToMany(Gost::class);
    }

    /**
     * «С этим товаром покупают» — admin-curated cross-category
     * recommendations. Symmetric in concept (see migration 0230);
     * sync uses syncComplementarySymmetric() below to keep both
     * directions in the pivot.
     */
    public function complementaryProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'complementary_products',
            'product_id',
            'complementary_product_id',
        )
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Symmetric M2M sync: writes both (this → each) and (each → this)
     * rows so the relationship reads cleanly from either side and the
     * admin can manage links from either product's form.
     *
     * Wrapped in a transaction so a partial failure doesn't leave the
     * pivot half-mirrored. Self-references are filtered out — a
     * product can't be its own complement.
     *
     * @param  array<int|string>  $productIds
     */
    public function syncComplementarySymmetric(array $productIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $productIds),
            fn (int $id) => $id > 0 && $id !== $this->id,
        )));

        DB::transaction(function () use ($ids) {
            // 1. Forward — this product's row reflects the admin's choice.
            $this->complementaryProducts()->sync($ids);

            // 2. Inverse — make sure each chosen product carries us back.
            foreach ($ids as $cid) {
                DB::table('complementary_products')->updateOrInsert(
                    ['product_id' => $cid, 'complementary_product_id' => $this->id],
                    ['sort_order' => 0],
                );
            }

            // 3. Cleanup inverse rows for products the admin removed
            //    from the list — otherwise old picks linger pointing
            //    back at us.
            DB::table('complementary_products')
                ->where('complementary_product_id', $this->id)
                ->whereNotIn('product_id', $ids ?: [0])
                ->delete();
        });
    }

    /**
     * «Также в этой категории» — auto-derived siblings from the
     * product's first category. Excludes the current product, soft-
     * deleted / unpublished / unlisted rows, and an optional list of
     * other ids (used to dedupe against the complementary block on
     * the same detail page).
     *
     * @param  array<int>  $exclude
     * @return Collection<int, Product>
     */
    public function relatedInCategory(int $limit = 6, array $exclude = []): Collection
    {
        $category = $this->relationLoaded('categories')
            ? $this->categories->first()
            : $this->categories()->first();

        if ($category === null) {
            return new Collection;
        }

        return $category->products()
            ->where('products.published', true)
            ->where('products.listed', true)
            ->whereNotIn('products.id', array_merge($exclude, [$this->id]))
            ->with(['categories:id,slug', 'gosts'])
            ->limit($limit)
            ->get();
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
        // Unified image collection — was split into blueprint / real /
        // gallery before; merged 2026-05-29 because the legacy split
        // didn't match how clients actually mix photos and schematics.
        // Order in Filament drag-handle = display order in catalog.
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // nonOptimized() — Spatie's default Image::optimize() reaches
        // for OptimizerChainFactory::create() which spawns jpegoptim /
        // pngquant via Symfony Process → needs proc_open. Plesk shared
        // disables proc_open, so optimization throws and the conversion
        // file never lands on disk. Disabling per conversion is the
        // load-bearing fix; the config-level image_optimizers=[] only
        // covers ONE of the two optimizer paths media-library walks.
        $this->addMediaConversion('thumb')->width(300)->height(300)->nonOptimized();
        $this->addMediaConversion('card')->width(600)->height(600)->nonOptimized();
        $this->addMediaConversion('og')->width(1200)->height(630)->nonOptimized();
        $this->addMediaConversion('mobile')->width(768)->nonOptimized();
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

    /**
     * Listed = appears in catalog navigation / category listings /
     * sitemap. Direct URL still works when listed=false (as long as
     * published=true) so externally-linked legacy URLs keep their SEO
     * weight.
     */
    public function scopeListed(Builder $q): Builder
    {
        return $q->where('listed', true);
    }
}
