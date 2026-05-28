<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('№')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable()
                    ->description(fn (Order $r) => $r->customer_company_name),

                TextColumn::make('customer_phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('total')
                    ->label('Сумма')
                    ->money('KZT')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Оплата')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state) => $state->label()),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(OrderStatus::class)
                    ->multiple(),

                SelectFilter::make('payment_method')
                    ->label('Способ оплаты')
                    ->options(PaymentMethod::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadInvoice')
                    ->label('PDF счёт')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn (Order $r) => filled($r->invoice_pdf_path))
                    ->url(fn (Order $r) => $r->invoiceUrl(), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([
                BulkActionGroup::make(self::workflowBulkActions()),
            ]);
    }

    /**
     * Bulk actions for moving orders along the status workflow.
     * Each action stamps `status_history` via Order::appendStatusHistory()
     * so we keep an audit trail of who-changed-what-when.
     *
     * @return array<int, BulkAction>
     */
    private static function workflowBulkActions(): array
    {
        $transitions = [
            ['confirm',   'Подтвердить',     OrderStatus::Confirmed, 'success',   'heroicon-o-check-circle'],
            ['invoice',   'Счёт выставлен',  OrderStatus::Invoiced,  'info',      'heroicon-o-document-text'],
            ['paid',      'Отметить оплачен', OrderStatus::Paid,      'success',   'heroicon-o-banknotes'],
            ['shipped',   'Отгружен',        OrderStatus::Shipped,   'info',      'heroicon-o-truck'],
            ['completed', 'Завершён',        OrderStatus::Completed, 'success',   'heroicon-o-check-badge'],
            ['cancel',    'Отменить',        OrderStatus::Cancelled, 'danger',    'heroicon-o-x-circle'],
        ];

        $actions = [];
        foreach ($transitions as [$key, $label, $to, $color, $icon]) {
            $actions[] = BulkAction::make($key)
                ->label($label)
                ->icon($icon)
                ->color($color)
                ->requiresConfirmation()
                ->action(fn (Collection $records) => self::applyStatus($records, $to));
        }

        return $actions;
    }

    private static function applyStatus(Collection $records, OrderStatus $to): void
    {
        $userId = auth()->id();

        DB::transaction(function () use ($records, $to, $userId): void {
            /** @var Order $order */
            foreach ($records as $order) {
                if ($order->status === $to) {
                    continue;
                }
                $order->appendStatusHistory($order->status, $to, $userId, null);
                $order->status = $to;
                $order->save();
            }
        });
    }
}
