<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Article;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Статья')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Заголовок')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, ?Article $record) {
                            if ($record === null && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(Article::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),

                    DateTimePicker::make('published_at')
                        ->label('Дата публикации')
                        ->seconds(false)
                        ->helperText('Пусто = черновик. Будущая дата = запланировано (видно только админу).')
                        ->columnSpanFull(),

                    Textarea::make('excerpt')
                        ->label('Краткое описание (excerpt)')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Показывается в списке статей и используется как fallback для meta_description.')
                        ->columnSpanFull(),

                    SpatieMediaLibraryFileUpload::make('cover')
                        ->label('Обложка статьи')
                        ->collection('cover')
                        ->image()
                        ->imageEditor()
                        ->helperText('1200×630 — идеально для OG + hero.')
                        ->columnSpanFull(),

                    RichEditor::make('content')
                        ->label('Содержимое')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList',
                            'blockquote', 'codeBlock', 'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            SeoSection::make(),
        ]);
    }
}
