<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Article;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
                    Select::make('blog_category_id')
                        ->label('Рубрика')
                        ->relationship('blogCategory', 'name', fn ($q) => $q->where('published', true))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Рубрика-хаб. Используется в breadcrumb, related-блоке и CollectionPage schema.')
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label('Заголовок (H1)')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, ?Article $record) {
                            if ($record === null && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->columnSpanFull(),

                    TextInput::make('subtitle')
                        ->label('Подзаголовок')
                        ->maxLength(300)
                        ->helperText('Альтернативный заголовок под H1. Попадёт в schema.org alternativeHeadline.')
                        ->columnSpanFull(),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(Article::class, 'slug', ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('URL: /blog/{slug}. При смене старый URL отредиректится на новый (301).'),

                    DateTimePicker::make('published_at')
                        ->label('Дата публикации')
                        ->seconds(false)
                        ->helperText('Пусто = черновик. Будущая дата = запланировано.'),

                    Textarea::make('excerpt')
                        ->label('Краткое описание (excerpt)')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Показывается в листингах и в meta_description если та пуста.')
                        ->columnSpanFull(),

                    SpatieMediaLibraryFileUpload::make('cover')
                        ->label('Обложка статьи')
                        ->collection('cover')
                        ->disk('public')
                        ->image()
                        ->rules(['dimensions:min_width=1200'])
                        ->helperText('Минимум 1200 px по ширине. Генерируются конверсии 1:1, 4:3, 16:9 для Article schema image[] — апскейл из меньшего портит SERP-thumbnail.')
                        ->columnSpanFull(),

                    RichEditor::make('content')
                        ->label('Содержимое')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList',
                            'blockquote', 'codeBlock', 'attachFiles',
                        ])
                        ->helperText('Используй H2/H3 для разделов — из них автоматически собирается оглавление (TOC) и AI-extraction-блоки.')
                        ->columnSpanFull(),
                ]),

            Section::make('Авто-статистика и обновления')
                ->description('Авторасчёт + read-only поле даты значимого обновления (управляется через action «Пометить обновлённой» в шапке).')
                ->collapsed()
                ->columns(3)
                ->schema([
                    TextInput::make('word_count')
                        ->label('Слов')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('—'),

                    TextInput::make('reading_minutes')
                        ->label('Чтение (мин)')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('—'),

                    // Read-only. Изменяется ТОЛЬКО через action в шапке —
                    // это защищает от ручной подделки freshness-сигнала
                    // (Google Helpful Content Update карает за такое).
                    DateTimePicker::make('updated_content_at')
                        ->label('Дата значимого обновления')
                        ->seconds(false)
                        ->disabled()
                        ->dehydrated(false),
                ]),

            SeoSection::make(),
        ]);
    }
}
