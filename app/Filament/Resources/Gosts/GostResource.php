<?php

declare(strict_types=1);

namespace App\Filament\Resources\Gosts;

use App\Filament\Resources\Gosts\Pages\CreateGost;
use App\Filament\Resources\Gosts\Pages\EditGost;
use App\Filament\Resources\Gosts\Pages\ListGosts;
use App\Filament\Resources\Gosts\Schemas\GostForm;
use App\Filament\Resources\Gosts\Tables\GostsTable;
use App\Models\Gost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GostResource extends Resource
{
    protected static ?string $model = Gost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $navigationLabel = 'ГОСТы и Серии';

    protected static ?string $modelLabel = 'ГОСТ / Серия';

    protected static ?string $pluralModelLabel = 'ГОСТы и Серии';

    protected static string|null|\UnitEnum $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return GostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGosts::route('/'),
            'create' => CreateGost::route('/create'),
            'edit' => EditGost::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
