<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Product;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
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
                        ->afterStateUpdated(function ($state, $set, ?Product $record) {
                            if ($record === null && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(Product::class, 'slug', ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('При смене старый URL отредиректится на новый (301).'),

                    TextInput::make('sku')
                        ->label('Артикул (SKU)')
                        ->required()
                        ->unique(Product::class, 'sku', ignoreRecord: true)
                        ->maxLength(64),

                    TextInput::make('gost')
                        ->label('ГОСТ / Серия')
                        ->maxLength(120)
                        ->placeholder('ГОСТ 8020-90')
                        ->helperText('Технический стандарт. Важен для B2B-поиска.'),

                    Select::make('categories')
                        ->label('Категории')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Описание')
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList',
                            'blockquote', 'codeBlock',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make('Размеры и характеристики')
                ->description('Параметры изделия. Используются для фильтрации в каталоге и в Schema.org-разметке.')
                ->columns(2)
                ->schema([
                    KeyValue::make('dimensions')
                        ->label('Габариты (ключ-значение)')
                        ->keyLabel('Параметр')
                        ->valueLabel('Значение')
                        ->keyPlaceholder('diameter')
                        ->valuePlaceholder('1500')
                        ->reorderable()
                        ->addActionLabel('+ параметр')
                        ->helperText('Например: diameter=1500, height=900, wall=90. Единицы измерения — мм.')
                        ->columnSpanFull(),

                    TextInput::make('weight_kg')
                        ->label('Вес, кг')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0),

                    Toggle::make('in_stock')
                        ->label('В наличии')
                        ->default(true),
                ]),

            Section::make('Цена')
                ->description('Если цена скрыта — на сайте показывается кнопка «Запросить цену».')
                ->columns(3)
                ->schema([
                    Toggle::make('price_visible')
                        ->label('Показывать цену на сайте')
                        ->default(false)
                        ->live()
                        ->columnSpanFull(),

                    TextInput::make('price')
                        ->label('Цена, ₸')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->visible(fn ($get) => $get('price_visible') === true),

                    TextInput::make('price_unit')
                        ->label('Единица')
                        ->default('за шт')
                        ->visible(fn ($get) => $get('price_visible') === true),

                    TextInput::make('unit_for_order')
                        ->label('Единица в заказе')
                        ->default('шт'),
                ]),

            Section::make('Изображения')
                ->description('Чертёж — схематичное изображение с размерами. Реальное фото — фотография самого изделия. Оба показываются на странице товара с переключателем.')
                ->columns(2)
                ->schema([
                    SpatieMediaLibraryFileUpload::make('image_blueprint')
                        ->label('Чертёж (схема с размерами)')
                        ->collection('blueprint')
                        ->image()
                        ->imageEditor()
                        ->helperText('1 файл, оптимально 1200×1200'),

                    SpatieMediaLibraryFileUpload::make('image_real')
                        ->label('Реальное фото изделия')
                        ->collection('real')
                        ->image()
                        ->imageEditor()
                        ->helperText('1 файл, оптимально 1200×1200'),

                    SpatieMediaLibraryFileUpload::make('gallery')
                        ->label('Доп. фотографии (галерея)')
                        ->collection('gallery')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),

            Section::make('Публикация')
                ->columns(3)
                ->schema([
                    Toggle::make('published')
                        ->label('Опубликовано')
                        ->default(false),

                    Toggle::make('featured')
                        ->label('Рекомендуемое (для главной)')
                        ->default(false),
                ]),

            SeoSection::make(),
        ]);
    }
}
