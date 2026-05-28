<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('downloadInvoice')
                ->label('Скачать PDF счёт')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (Order $record) => filled($record->invoice_pdf_path))
                ->url(fn (Order $record) => $record->invoiceUrl(), shouldOpenInNewTab: true),
        ];
    }
}
