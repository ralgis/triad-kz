<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogCategories\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\BlogCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Рубрика')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, ?BlogCategory $record) {
                            if ($record === null && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(BlogCategory::class, 'slug', ignoreRecord: true)
                        ->maxLength(80)
                        ->helperText('URL: /blog/category/{slug}. При смене старый отредиректится на новый.'),

                    TextInput::make('order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->helperText('Меньше = раньше в /blog.'),

                    Toggle::make('published')
                        ->label('Опубликовано')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('listed')
                        ->label('Показывать в навигации блога')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Снять — рубрика доступна по прямому URL, но не появляется в списке /blog.'),

                    RichEditor::make('description')
                        ->label('Описание рубрики')
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList',
                            'blockquote',
                        ])
                        ->helperText('Pillar-style 300-500 слов. Отображается на /blog/category/{slug} вверху перед статьями.')
                        ->columnSpanFull(),

                    SpatieMediaLibraryFileUpload::make('cover')
                        ->label('Обложка рубрики')
                        ->collection('cover')
                        ->disk('public')
                        ->image()
                        ->columnSpanFull(),
                ]),

            SeoSection::make(),
        ]);
    }
}
