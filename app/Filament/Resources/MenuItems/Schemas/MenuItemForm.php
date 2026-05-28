<?php

declare(strict_types=1);

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Enums\MenuPosition;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class MenuItemForm
{
    /**
     * Map of polymorphic morph keys to human labels and the Eloquent model
     * we list options from. We use the FQCN as the linkable_type value to
     * match the default Laravel morph-map (no custom morph aliases).
     */
    private const LINKABLE_TYPES = [
        Category::class => 'Категория каталога',
        Product::class => 'Товар',
        Article::class => 'Статья',
        Page::class => 'Страница',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Пункт меню')
                ->columns(2)
                ->schema([
                    TextInput::make('label')
                        ->label('Текст ссылки')
                        ->required()
                        ->maxLength(120),

                    Select::make('position')
                        ->label('Расположение')
                        ->options(MenuPosition::class)
                        ->default(MenuPosition::Header->value)
                        ->required(),

                    Select::make('parent_id')
                        ->label('Родительский пункт (для подменю)')
                        ->relationship(
                            name: 'parent',
                            titleAttribute: 'label',
                            modifyQueryUsing: fn ($query) => $query->whereNull('parent_id'),
                        )
                        ->searchable()
                        ->preload()
                        ->placeholder('— верхний уровень —'),

                    TextInput::make('order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->helperText('Меньше = раньше'),

                    Toggle::make('open_in_new_tab')
                        ->label('Открывать в новой вкладке')
                        ->default(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Куда ведёт')
                ->description('Либо выбери внутреннюю сущность сайта, либо вставь URL вручную. Если заполнены оба, приоритет у выбранной сущности.')
                ->columns(2)
                ->schema([
                    Select::make('linkable_type')
                        ->label('Тип сущности')
                        ->options(self::LINKABLE_TYPES)
                        ->live()
                        ->afterStateUpdated(fn ($state, $set) => $set('linkable_id', null))
                        ->placeholder('— внешняя ссылка —'),

                    Select::make('linkable_id')
                        ->label('Сущность')
                        ->options(fn ($get) => self::optionsForType((string) $get('linkable_type')))
                        ->searchable()
                        ->disabled(fn ($get) => blank($get('linkable_type')))
                        ->placeholder('—'),

                    TextInput::make('url')
                        ->label('Внешний URL (если ссылка наружу)')
                        ->url()
                        ->placeholder('https://example.com/contact')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @return array<int|string, string>
     */
    private static function optionsForType(string $type): array
    {
        if (! array_key_exists($type, self::LINKABLE_TYPES)) {
            return [];
        }

        /** @var class-string<Model> $type */
        return $type::query()
            ->orderBy(self::titleColumnFor($type))
            ->limit(200)
            ->pluck(self::titleColumnFor($type), 'id')
            ->all();
    }

    private static function titleColumnFor(string $type): string
    {
        return match ($type) {
            Article::class, Page::class => 'title',
            default => 'name',
        };
    }
}
