<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Category;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get, ?Category $record) {
                            // Только при создании — после создания slug фиксируется
                            // и любая правка пойдёт через 301-redirect observer.
                            if ($record === null && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(Category::class, 'slug', ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('Латиница, без пробелов. При смене старый URL автоматически отредиректится на новый (301).'),

                    Select::make('parent_id')
                        ->label('Родительская категория')
                        ->relationship('parent', 'name', fn ($query, ?Category $record) => $query
                            ->when($record, fn ($q) => $q->where('id', '!=', $record->id)))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Пусто = категория верхнего уровня.'),

                    TextInput::make('order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->helperText('Меньше = раньше в списке.'),

                    Toggle::make('published')
                        ->label('Опубликовано')
                        ->default(true)
                        ->helperText('Выключи чтобы вообще скрыть категорию (404 для всех)'),

                    Toggle::make('listed')
                        ->label('Показывать в каталоге')
                        ->default(true)
                        ->helperText('Выключи чтобы убрать из навигации каталога, оставив прямой URL рабочим'),

                    RichEditor::make('description')
                        ->label('Описание')
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList',
                            'blockquote', 'codeBlock',
                        ])
                        ->columnSpanFull(),

                    SpatieMediaLibraryFileUpload::make('cover')
                        ->label('Обложка категории')
                        ->collection('cover')
                        ->disk('public')
                        ->image()
                        ->columnSpanFull(),
                ]),

            SeoSection::make(),
        ]);
    }
}
