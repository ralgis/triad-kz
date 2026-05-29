<?php

declare(strict_types=1);

namespace App\Filament\Resources\Gosts\Pages;

use App\Filament\Resources\Gosts\GostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGosts extends ListRecords
{
    protected static string $resource = GostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
