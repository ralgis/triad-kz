<?php

declare(strict_types=1);

namespace App\Filament\Resources\Gosts\Pages;

use App\Filament\Resources\Gosts\GostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditGost extends EditRecord
{
    protected static string $resource = GostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
