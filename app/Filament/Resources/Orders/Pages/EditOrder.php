<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Tracks the original status so we can stamp status_history on change.
     */
    private ?OrderStatus $originalStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        /** @var Order $record */
        $record = $this->record;
        $rawOriginal = $record->getRawOriginal('status');
        $this->originalStatus = $rawOriginal !== null
            ? OrderStatus::from((string) $rawOriginal)
            : null;
    }

    protected function afterSave(): void
    {
        /** @var Order $record */
        $record = $this->record;
        $newStatus = $record->status;

        if ($this->originalStatus !== null && $newStatus !== $this->originalStatus) {
            $record->appendStatusHistory($this->originalStatus, $newStatus, auth()->id(), 'Edited via admin');
            $record->saveQuietly();
        }
    }
}
