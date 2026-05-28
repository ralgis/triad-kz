<?php

declare(strict_types=1);

namespace App\Filament\Resources\MenuItems\Tables;

use App\Enums\MenuPosition;
use App\Models\MenuItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('position')
            ->defaultSort('order')
            ->reorderable('order')
            ->columns([
                TextColumn::make('label')
                    ->label('Текст')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('target')
                    ->label('Куда ведёт')
                    ->state(fn (MenuItem $r) => self::targetDescription($r))
                    ->wrap(),

                TextColumn::make('position')
                    ->label('Расположение')
                    ->badge(),

                TextColumn::make('parent.label')
                    ->label('Родитель')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable(),

                IconColumn::make('open_in_new_tab')
                    ->label('В новой')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->label('Расположение')
                    ->options(MenuPosition::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function targetDescription(MenuItem $item): string
    {
        if ($item->linkable_type && $item->linkable) {
            $shortType = class_basename($item->linkable_type);
            $title = $item->linkable->name ?? $item->linkable->title ?? '#'.$item->linkable_id;

            return "{$shortType}: {$title}";
        }

        return $item->url ?: '—';
    }
}
