<?php

declare(strict_types=1);

namespace App\Filament\Resources\Gosts\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Gost;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class GostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    Select::make('kind')
                        ->label('Тип')
                        ->required()
                        ->options([
                            Gost::KIND_GOST => 'ГОСТ',
                            Gost::KIND_SERIYA => 'Серия',
                        ])
                        ->default(Gost::KIND_GOST)
                        ->native(false),

                    TextInput::make('label')
                        ->label('Название (как отображается)')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('ГОСТ 8020-90')
                        ->helperText('Полное название с приставкой. Будет видно на сайте и в карточках товаров.')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, ?Gost $record) {
                            if ($record === null && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('code')
                        ->label('Цифровой код (для поиска при импорте)')
                        ->maxLength(100)
                        ->placeholder('8020-90')
                        ->helperText('Только цифры/точки/дефисы. Помогает матчить старые описания товаров со справочником.'),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(Gost::class, 'slug', ignoreRecord: true)
                        ->maxLength(200)
                        ->helperText('Используется как якорь на /gosts/#{slug}. При смене старый URL отредиректится на новый (301).'),

                    TextInput::make('sort_order')
                        ->label('Порядок отображения')
                        ->numeric()
                        ->default(0)
                        ->helperText('Меньше = выше в аккордеоне.'),

                    RichEditor::make('description')
                        ->label('Описание (содержимое аккордеона)')
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h3', 'bulletList', 'orderedList',
                            'blockquote',
                        ])
                        ->columnSpanFull()
                        ->helperText('Текст, который раскрывается под заголовком на странице /gosts/.'),
                ]),

            SeoSection::make(),
        ]);
    }
}
