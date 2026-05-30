<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ArticleType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->collection('cover')
                    ->conversion('thumb')
                    ->label('')
                    ->size(40),

                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('blogCategory.name')
                    ->label('Рубрика')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('article_type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                        'pillar' => 'success',
                        'guide' => 'info',
                        'comparison' => 'warning',
                        'news' => 'danger',
                        'case' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => $state instanceof ArticleType ? $state->label() : (string) $state)
                    ->toggleable(),

                IconColumn::make('is_pillar')
                    ->label('Pillar')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reading_minutes')
                    ->label('Чтение')
                    ->suffix(' мин')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('word_count')
                    ->label('Слов')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('slug')
                    ->label('URL')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')
                    ->label('Опубликовано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('— черновик —'),

                IconColumn::make('noindex')
                    ->label('noindex')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('published_at')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Опубликованные')
                    ->falseLabel('Черновики')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('published_at')->where('published_at', '<=', now()),
                        false: fn ($q) => $q->whereNull('published_at'),
                        blank: fn ($q) => $q,
                    ),
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
