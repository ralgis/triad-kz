<?php

declare(strict_types=1);

namespace App\Filament\Resources\Gosts\Tables;

use App\Models\Gost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class GostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                BadgeColumn::make('kind')
                    ->label('Тип')
                    ->colors([
                        'primary' => Gost::KIND_GOST,
                        'success' => Gost::KIND_SERIYA,
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === Gost::KIND_GOST ? 'ГОСТ' : 'Серия'),

                TextColumn::make('label')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Код')
                    ->color('gray')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('products_count')
                    ->label('Товаров')
                    ->counts('products')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'success'),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Тип')
                    ->options([
                        Gost::KIND_GOST => 'ГОСТ',
                        Gost::KIND_SERIYA => 'Серия',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
