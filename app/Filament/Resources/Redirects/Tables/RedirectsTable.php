<?php

declare(strict_types=1);

namespace App\Filament\Resources\Redirects\Tables;

use App\Models\Redirect;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('hit_count', 'desc')
            ->columns([
                TextColumn::make('from')
                    ->label('Откуда')
                    ->searchable()
                    ->wrap()
                    ->copyable(),

                TextColumn::make('to')
                    ->label('Куда')
                    ->searchable()
                    ->wrap()
                    ->copyable(),

                TextColumn::make('status')
                    ->label('Код')
                    ->badge()
                    ->color(fn (int $state) => $state === 301 ? 'success' : 'warning'),

                TextColumn::make('hit_count')
                    ->label('Срабатываний')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('last_hit_at')
                    ->label('Последнее')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('— ни разу —'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Код')
                    ->options([301 => '301', 302 => '302']),
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

    /**
     * Bulk-upsert a parsed list of redirects.
     * Used by ListRedirects → ImportCsvAction header action.
     *
     * Each row is ['from' => '/old', 'to' => '/new', 'status' => 301].
     * Existing `from` paths are updated, new ones inserted.
     *
     * @param list<array{from: string, to: string, status: int}> $rows
     * @return array{created: int, updated: int}
     */
    public static function upsertBatch(array $rows): array
    {
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $model = Redirect::firstOrNew(['from' => $row['from']]);
            $existed = $model->exists;
            $model->to = $row['to'];
            $model->status = $row['status'];
            $model->save();

            $existed ? $updated++ : $created++;
        }

        return ['created' => $created, 'updated' => $updated];
    }
}
