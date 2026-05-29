<?php

declare(strict_types=1);

namespace App\Filament\Resources\Gosts\Pages;

use App\Filament\Resources\Gosts\GostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGost extends CreateRecord
{
    protected static string $resource = GostResource::class;
}
