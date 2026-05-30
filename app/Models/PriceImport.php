<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log row for one applied price-import. See migration
 * 2026_05_31_010000 for shape rationale.
 *
 * @property array<string, mixed>|null $notes
 */
class PriceImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'rows_processed',
        'rows_updated',
        'rows_skipped',
        'imported_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rows_processed' => 'integer',
            'rows_updated' => 'integer',
            'rows_skipped' => 'integer',
            'notes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
