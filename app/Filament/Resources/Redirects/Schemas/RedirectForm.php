<?php

declare(strict_types=1);

namespace App\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Редирект')
                ->columns(2)
                ->schema([
                    TextInput::make('from')
                        ->label('Откуда (старый путь)')
                        ->required()
                        ->maxLength(2048)
                        ->placeholder('/old-url/')
                        ->helperText('Относительный путь от корня сайта, с ведущим слэшем. БЕЗ домена и без query-string.')
                        ->unique(ignoreRecord: true),

                    TextInput::make('to')
                        ->label('Куда (новый путь / URL)')
                        ->required()
                        ->maxLength(2048)
                        ->placeholder('/catalog/beton-koltsa/')
                        ->helperText('Относительный путь или полный URL для внешних редиректов.'),

                    Select::make('status')
                        ->label('Код')
                        ->options([
                            301 => '301 — постоянный (рекомендуется для SEO)',
                            302 => '302 — временный',
                        ])
                        ->default(301)
                        ->required(),

                    TextInput::make('hit_count')
                        ->label('Срабатываний')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Счётчик инкрементится middleware-ом на каждое попадание.'),
                ]),
        ]);
    }
}
