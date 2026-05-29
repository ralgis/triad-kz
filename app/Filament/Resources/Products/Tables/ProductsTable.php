<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Models\Gost;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('gosts'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('real')
                    ->collection('real')
                    ->conversion('thumb')
                    ->label('')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('sku')
                    ->label('Артикул')
                    ->searchable()
                    ->copyable(),

                // Was reading the dropped `products.gost` text column —
                // hence everywhere «—» on prod after migration 0200
                // dropped it. Now pulls fullLabel() off the M2M
                // `gosts` relation so the admin sees what the public
                // pages render. «ГОСТ» renamed to «Стандарт» because
                // the underlying type covers ГОСТ + Серия + СТ ТОО
                // (and any future СТ РК / ТУ) — narrower label was
                // misleading.
                TextColumn::make('standards')
                    ->label('Стандарт')
                    ->badge()
                    ->getStateUsing(fn (Product $record): array => $record->gosts
                        ->map(fn (Gost $g): string => $g->fullLabel())
                        ->all())
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('categories.name')
                    ->label('Категории')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('KZT')
                    ->sortable()
                    ->placeholder('скрыта'),

                IconColumn::make('price_visible')
                    ->label('Цена видна')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('in_stock')
                    ->label('В нал.')
                    ->boolean(),

                IconColumn::make('published')
                    ->label('Опубл.')
                    ->boolean(),

                IconColumn::make('featured')
                    ->label('★')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star'),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('published')->label('Опубликовано'),
                TernaryFilter::make('featured')->label('Рекомендуемые'),
                TernaryFilter::make('in_stock')->label('В наличии'),
                SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->preload()
                    ->multiple(),
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
