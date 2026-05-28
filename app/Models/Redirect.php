<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 301-map row. Created two ways:
 *   1) Bulk-imported during cutover (Phase 4) from CSV of old triad.kz URLs.
 *   2) Auto-created by SlugObserver whenever Category/Product/Article slug
 *      changes in admin — so SEO doesn't break on renames.
 *
 * Lookup happens in HandleRedirects middleware on 404.
 */
class Redirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'from',
        'to',
        'status',
        'hit_count',
        'last_hit_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'hit_count' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    /**
     * Normalize incoming path to canonical form: leading slash, no
     * trailing slash (except for bare "/"). Admin can paste "foo/" or
     * "/foo/" or "foo" — DB row stays consistent so middleware lookup
     * by request path matches.
     */
    protected function from(): Attribute
    {
        return Attribute::make(
            set: fn (?string $v) => self::normalizePath($v),
        );
    }

    protected function to(): Attribute
    {
        return Attribute::make(
            set: fn (?string $v) => self::normalizePath($v),
        );
    }

    public static function normalizePath(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim($v);
        if ($v === '' || $v === '/') {
            return '/';
        }
        // Preserve absolute URLs in `to` (e.g. https://triad.kz/external).
        if (preg_match('#^https?://#i', $v)) {
            return $v;
        }

        return '/'.trim($v, '/');
    }

    /**
     * Atomic hit-counter increment + last-hit stamp.
     * Called from HandleRedirects middleware on each match.
     */
    public function recordHit(): void
    {
        $this->newQuery()
            ->whereKey($this->getKey())
            ->update([
                'hit_count' => $this->hit_count + 1,
                'last_hit_at' => now(),
            ]);
    }
}
