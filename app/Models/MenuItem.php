<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'linkable_type',
        'linkable_id',
        'url',
        'position',
        'parent_id',
        'order',
        'open_in_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'open_in_new_tab' => 'boolean',
            'position' => MenuPosition::class,
        ];
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
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
     * Final URL — resolves polymorphic target if present, else falls back
     * to the literal url column, else "#".
     */
    public function resolvedUrl(): string
    {
        if ($this->linkable && method_exists($this->linkable, 'url')) {
            return $this->linkable->url();
        }

        return $this->url ?? '#';
    }

    // ---- Scopes ----

    public function scopeForPosition(Builder $q, MenuPosition $position): Builder
    {
        return $q->where('position', $position);
    }

    public function scopeTopLevel(Builder $q): Builder
    {
        return $q->whereNull('parent_id')->orderBy('order');
    }
}
