<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Shared SEO-fields plumbing for content models (Category, Product, Article, Page).
 *
 * Provides:
 *   - $fillable additions (callers must merge into their own $fillable)
 *   - casts() additions for SEO columns
 *   - sensible fallback accessors so the view layer can do {{ $model->seoTitle }}
 *     without checking for nulls everywhere
 *
 * Usage on a model:
 *
 *     use App\Traits\HasSeo;
 *
 *     class Product extends Model
 *     {
 *         use HasSeo;
 *
 *         protected $fillable = [...own fields..., ...$this->seoFillable()];
 *     }
 *
 * NOTE: We DON'T merge fillable automatically via trait boot() — it makes
 * model definition confusing for readers. Each model spells out the seo fields
 * explicitly in $fillable for grep-ability.
 */
trait HasSeo
{
    /**
     * Casts that any HasSeo-using model should include in its casts() method.
     *
     * @return array<string, string>
     */
    public function seoCasts(): array
    {
        return [
            'noindex' => 'boolean',
            'structured_data_override' => 'array',
        ];
    }

    /**
     * Effective <title> tag value with sensible fallback.
     */
    public function seoTitle(): string
    {
        return $this->meta_title ?: ($this->name ?? $this->title ?? '');
    }

    /**
     * Effective <meta description> with fallback to excerpt or first 160
     * chars of description/content.
     */
    public function seoDescription(): string
    {
        if (! empty($this->meta_description)) {
            return $this->meta_description;
        }
        $fallback = $this->excerpt ?? $this->description ?? $this->content ?? '';
        $clean = trim(strip_tags((string) $fallback));

        return mb_substr($clean, 0, 160);
    }
}
