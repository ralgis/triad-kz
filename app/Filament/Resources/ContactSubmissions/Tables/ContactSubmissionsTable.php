<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactSubmissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Получена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('product.name')
                    ->label('Товар')
                    ->placeholder('— общая форма —')
                    ->wrap(),

                TextColumn::make('message')
                    ->label('Сообщение')
                    ->limit(80)
                    ->wrap()
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('with_product')
                    ->label('Только с привязкой к товару')
                    ->query(fn (Builder $q) => $q->whereNotNull('product_id')),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
