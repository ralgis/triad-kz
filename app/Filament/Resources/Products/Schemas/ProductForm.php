<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Gost;
use App\Models\Product;
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

                    Select::make('gosts')
                        ->label('Стандарт')
                        ->relationship('gosts', 'label', fn ($query) => $query->orderBy('sort_order')->orderBy('label'))
                        ->getOptionLabelFromRecordUsing(fn (Gost $r) => $r->fullLabel())
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('Выберите из справочника. Один товар может ссылаться на несколько стандартов (например, ГОСТ + соответствующую Серию).')
                        ->createOptionForm([
                            Select::make('kind')
                                ->label('Тип')
                                ->required()
                                ->options([
                                    Gost::KIND_GOST => 'ГОСТ',
                                    Gost::KIND_SERIYA => 'Серия',
                                    Gost::KIND_TOO => 'СТ ТОО',
                                ])
                                ->default(Gost::KIND_GOST)
                                ->native(false),
                            TextInput::make('label')
                                ->label('Номер стандарта (без приставки)')
                                ->required()
                                ->maxLength(200)
                                ->placeholder('8020-90')
                                ->helperText('Приставка «ГОСТ»/«Серия»/«СТ ТОО» подставится автоматически по типу.'),
                            TextInput::make('code')
                                ->label('Цифровой код (опц.)')
                                ->maxLength(100)
                                ->placeholder('8020-90'),
                        ]),

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

            Section::make('Геометрия')
                ->description('Размеры изделия в миллиметрах. Заполняются те поля, которые применимы к этой категории — остальные оставь пустыми.')
                ->columns(4)
                ->schema([
                    TextInput::make('length_mm')
                        ->label('Длина, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1),

                    TextInput::make('width_mm')
                        ->label('Ширина, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1),

                    TextInput::make('height_mm')
                        ->label('Высота, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1),

                    TextInput::make('thickness_mm')
                        ->label('Толщина, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1),

                    TextInput::make('inner_diameter_mm')
                        ->label('Внутр. диаметр, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->helperText('Для колец'),

                    TextInput::make('outer_diameter_mm')
                        ->label('Внеш. диаметр, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->helperText('Для колец'),

                    TextInput::make('plate_diameter_mm')
                        ->label('Диаметр плиты, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->helperText('Для плит'),

                    TextInput::make('hole_diameter_mm')
                        ->label('Диаметр отверстия, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->helperText('Для плит перекрытия'),
                ]),

            Section::make('Материал и наличие')
                ->columns(4)
                ->schema([
                    Select::make('concrete_grade')
                        ->label('Марка бетона')
                        ->options([
                            'M200' => 'M200',
                            'M300' => 'M300',
                            'M350' => 'M350',
                            'M400' => 'M400',
                        ])
                        ->searchable()
                        ->native(false)
                        ->placeholder('—'),

                    TextInput::make('concrete_volume_m3')
                        ->label('Объём бетона, м³')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.001),

                    TextInput::make('weight_t')
                        ->label('Вес, т')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.001)
                        ->helperText('В тоннах. Например, 0.68 или 1.275.'),

                    TextInput::make('steel_kg')
                        ->label('Расход стали, кг')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01),

                    Toggle::make('in_stock')
                        ->label('В наличии')
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            Section::make('Параметры сетки сварной')
                ->description('Заполняются только для товаров категории «Сетка сварная».')
                ->columns(3)
                ->collapsed()
                ->schema([
                    TextInput::make('mesh_rod_diameter_mm')
                        ->label('Диаметр прутка, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1),

                    TextInput::make('mesh_cell_length_mm')
                        ->label('Длина ячейки, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1)
                        ->helperText('Для квадратных ячеек оставь ширину пустой.'),

                    TextInput::make('mesh_cell_width_mm')
                        ->label('Ширина ячейки, мм')
                        ->numeric()
                        ->minValue(0)
                        ->step(1),
                ]),

            Section::make('Цена')
                ->description('Если цена скрыта — на сайте показывается кнопка «Запросить цену». Значения цены сохраняются даже когда скрыты, чтобы их можно было быстро вернуть.')
                ->columns(3)
                ->schema([
                    Toggle::make('price_visible')
                        ->label('Показывать цену на сайте')
                        ->default(false)
                        ->live()
                        ->columnSpanFull(),

                    // When price_visible=false the three fields stay
                    // editable in DB terms (saved on submit) but get
                    // a disabled-look so the admin sees they don't
                    // affect the public page. Cheaper to grey out than
                    // hide-and-lose-context.
                    TextInput::make('price')
                        ->label('Цена, ₸')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->disabled(fn ($get) => $get('price_visible') !== true),

                    TextInput::make('price_unit')
                        ->label('Единица')
                        ->default('за шт')
                        ->disabled(fn ($get) => $get('price_visible') !== true),

                    TextInput::make('unit_for_order')
                        ->label('Единица в заказе')
                        ->default('шт')
                        ->disabled(fn ($get) => $get('price_visible') !== true),
                ]),

            Section::make('Изображения')
                ->description('Загрузите фото и чертежи. Перетащите за уголок чтобы поменять порядок. Первое фото показывается на карточке товара и в OG-превью.')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('images')
                        ->collection('images')
                        ->disk('public')
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->image()
                        ->panelLayout('grid')
                        ->columnSpanFull(),
                ]),

            Section::make('Публикация')
                ->description('Опубликовано = вообще доступно (false → 404). Показывать в каталоге = в листингах и sitemap (false → прямой URL работает, но в нав не виден — для архивных позиций со старыми внешними ссылками).')
                ->columns(3)
                ->schema([
                    Toggle::make('published')
                        ->label('Опубликовано')
                        ->default(false),

                    Toggle::make('listed')
                        ->label('Показывать в каталоге')
                        ->default(true),

                    Toggle::make('featured')
                        ->label('Рекомендуемое (для главной)')
                        ->default(false),
                ]),

            SeoSection::make(),
        ]);
    }
}
