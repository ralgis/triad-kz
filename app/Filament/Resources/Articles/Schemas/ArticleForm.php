<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\ArticleType;
use App\Filament\Components\SeoSection;
use App\Models\Article;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                        ->helperText('Рубрика-хаб. Используется в breadcrumb, related-блоке и CollectionPage schema.'),

                    Select::make('article_type')
                        ->label('Тип статьи')
                        ->options(collect(ArticleType::cases())->mapWithKeys(
                            fn (ArticleType $t) => [$t->value => $t->label()],
                        ))
                        ->default(ArticleType::Guide->value)
                        ->required()
                        ->helperText('Драйвит render-логику (FAQ-блок, HowTo schema, сортировку).'),

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

            Section::make('Topic cluster (pillar/спутник)')
                ->description('Pillar = главная статья темы. Cluster = спутник, ссылается на pillar.')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('is_pillar')
                        ->label('Это pillar-статья')
                        ->helperText('Если включено, эта статья — pillar. На странице будет авто-блок «В этой теме» со списком всех cluster-статей.')
                        ->inline(false),

                    Select::make('pillar_id')
                        ->label('Pillar этой статьи')
                        ->relationship('pillar', 'title', fn ($q, ?Article $record) => $q
                            ->where('is_pillar', true)
                            ->when($record, fn ($q) => $q->whereKeyNot($record->id)))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Только для cluster-статей. NULL для pillar или standalone.'),
                ]),

            Section::make('Связанные сущности (M2M)')
                ->description('Прямые связи с каталогом и ГОСТами — заменяют tag-pattern.')
                ->collapsed()
                ->schema([
                    Select::make('products')
                        ->label('Связанные товары (для «С этим товаром покупают»)')
                        ->relationship('products', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('2-5 товаров. Появятся блоком в конце статьи + в Article.about[] schema.'),

                    Select::make('gosts')
                        ->label('Связанные ГОСТы / серии')
                        ->relationship('gosts', 'label')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('1-3 ГОСТа. Показываются в byline + Article.about[].'),

                    Select::make('catalogCategories')
                        ->label('Связанные категории каталога')
                        ->relationship('catalogCategories', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Cross-link в навигацию каталога.'),
                ]),

            Section::make('Размещение и видимость')
                ->collapsed()
                ->columns(3)
                ->schema([
                    Toggle::make('featured')
                        ->label('Featured (на главную /blog)')
                        ->inline(false),

                    DateTimePicker::make('pinned_until')
                        ->label('Sticky в категории до')
                        ->seconds(false)
                        ->helperText('Закрепить на верху своей рубрики до этой даты. Пусто = не sticky.'),

                    Toggle::make('toc_enabled')
                        ->label('Показывать TOC')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Snять для коротких статей без H2/H3 разделов.'),
                ]),

            Section::make('How-to шаги (Phase 3, опционально)')
                ->description('Для guide-статей с пошаговой инструкцией («Как монтировать колодец»). Эмитит HowTo JSON-LD для AI extraction (Google убрал SERP rich results 2023).')
                ->collapsed()
                ->schema([
                    Repeater::make('how_to_steps')
                        ->label('Шаги')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('name')
                                ->label('Название шага')
                                ->required()
                                ->maxLength(120),
                            Textarea::make('text')
                                ->label('Описание')
                                ->required()
                                ->rows(2)
                                ->maxLength(500),
                            TextInput::make('image')
                                ->label('URL изображения (необязательно)')
                                ->url()
                                ->maxLength(500),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Добавить шаг')
                        ->reorderable(true)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ]),

            Section::make('Использованные источники (Phase 3, опционально)')
                ->description('Внешние ссылки на ГОСТы, исследования, статьи. Отображаются блоком внизу с rel="external nofollow noopener".')
                ->collapsed()
                ->schema([
                    Repeater::make('external_sources')
                        ->label('Источники')
                        ->hiddenLabel()
                        ->columns(2)
                        ->schema([
                            TextInput::make('title')
                                ->label('Название')
                                ->required()
                                ->maxLength(200),
                            TextInput::make('url')
                                ->label('URL')
                                ->url()
                                ->maxLength(500),
                            TextInput::make('accessed_at')
                                ->label('Дата доступа')
                                ->placeholder('2026-05-30'),
                            TextInput::make('note')
                                ->label('Примечание')
                                ->maxLength(200),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Добавить источник')
                        ->reorderable(true)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                ]),

            Section::make('FAQ блок')
                ->description('Заполняй ТОЛЬКО при реальных вопросах из Wordstat/Search Console — не ради schema-маркапа. Пустой = блок не показывается.')
                ->collapsed()
                ->schema([
                    Repeater::make('faq')
                        ->label('Вопросы и ответы')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('question')
                                ->label('Вопрос')
                                ->required()
                                ->maxLength(200),
                            Textarea::make('answer')
                                ->label('Ответ')
                                ->required()
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Добавить Q&A')
                        ->reorderable(true)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null),
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
