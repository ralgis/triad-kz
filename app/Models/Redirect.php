<?php

declare(strict_types=1);

namespace App\Models;

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
