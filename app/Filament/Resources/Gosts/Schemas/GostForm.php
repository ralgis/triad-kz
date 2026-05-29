<?php

declare(strict_types=1);

namespace App\Filament\Resources\Gosts\Schemas;

use App\Filament\Components\SeoSection;
use App\Models\Gost;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                        ->native(false)
                        ->live(),

                    // Bare code/identifier only — the «ГОСТ»/«Серия»
                    // prefix is reactive via ->prefix() based on the
                    // kind field above. Stored value: «8020-90», not
                    // «ГОСТ 8020-90».
                    TextInput::make('label')
                        ->label('Номер стандарта')
                        ->required()
                        ->maxLength(200)
                        ->prefix(fn ($get) => match ($get('kind')) {
                            Gost::KIND_GOST => 'ГОСТ',
                            Gost::KIND_SERIYA => 'Серия',
                            default => null,
                        })
                        ->placeholder('8020-90')
                        ->helperText('Без приставки «ГОСТ»/«Серия» — она подставится автоматически по типу.')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get, ?Gost $record) {
                            if ($record === null && filled($state) && filled($get('kind'))) {
                                $set('slug', Str::slug($get('kind').' '.$state));
                            }
                        }),

                    TextInput::make('code')
                        ->label('Цифровой код (для матчинга при импорте)')
                        ->maxLength(100)
                        ->placeholder('8020-90')
                        ->helperText('Только цифры/точки/дефисы. Помогает связать товары со справочником по тексту описания.'),

                    TextInput::make('slug')
                        ->label('URL-slug')
                        ->required()
                        ->unique(Gost::class, 'slug', ignoreRecord: true)
                        ->maxLength(200)
                        ->helperText('Используется как якорь на /gosts/#{slug}. При смене старый URL отредиректится на новый (301).'),

                    TextInput::make('title')
                        ->label('Официальное полное название')
                        ->maxLength(500)
                        ->placeholder('Конструкции бетонные и железобетонные для колодцев…')
                        ->helperText('Используется в SEO-title и заголовке записи на странице.')
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Описание (содержимое аккордеона)')
                        ->toolbarButtons([
                            'bold', 'italic', 'strike', 'link',
                            'h3', 'bulletList', 'orderedList',
                            'blockquote',
                        ])
                        ->columnSpanFull()
                        ->helperText('Текст, который раскрывается под заголовком на странице /gosts/.'),

                    TextInput::make('sort_order')
                        ->label('Порядок отображения')
                        ->numeric()
                        ->default(0)
                        ->helperText('Меньше = выше в аккордеоне.'),
                ]),

            Section::make('Связи и статус')
                ->description('Если запись — Серия, укажи родительский ГОСТ. Если запись устарела — отметь и укажи замену.')
                ->columns(2)
                ->schema([
                    Toggle::make('is_current')
                        ->label('Действующая редакция в Казахстане')
                        ->default(true)
                        ->live()
                        ->columnSpanFull(),

                    DatePicker::make('introduced_at')
                        ->label('Дата введения в действие')
                        ->displayFormat('d.m.Y')
                        ->helperText('Когда стандарт официально вступил в силу. Для редакций, принятых в РК — дата введения в Казахстане.'),

                    DatePicker::make('effective_in_kz_until')
                        ->label('Действовал в РК до')
                        ->displayFormat('d.m.Y')
                        ->visible(fn ($get) => $get('is_current') === false)
                        ->helperText('Дата окончания действия в Казахстане (для устаревших).'),

                    Select::make('relates_to_gost_id')
                        ->label('Разработана в рамках ГОСТ (только для Серий)')
                        ->relationship('relatesToGost', 'label', fn ($query) => $query->where('kind', Gost::KIND_GOST)->orderBy('sort_order'))
                        ->getOptionLabelFromRecordUsing(fn (Gost $r) => $r->fullLabel())
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get) => $get('kind') === Gost::KIND_SERIYA)
                        ->helperText('Источник техусловий и номенклатуры. Оставь пусто для самостоятельных серий.'),

                    Select::make('superseded_by_id')
                        ->label('Заменён записью')
                        ->relationship('supersededBy', 'label', fn ($query) => $query->orderBy('sort_order'))
                        ->getOptionLabelFromRecordUsing(fn (Gost $r) => $r->fullLabel())
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get) => $get('is_current') === false)
                        ->helperText('Если есть в справочнике — выбери. Если нет — заполни поле «Заметка о замене» ниже.'),

                    Textarea::make('superseded_note')
                        ->label('Заметка о замене (если новой записи нет в справочнике)')
                        ->rows(2)
                        ->maxLength(500)
                        ->visible(fn ($get) => $get('is_current') === false)
                        ->placeholder('Например: «Заменён ГОСТ 8020-68» (исторический контекст без отдельной записи)')
                        ->columnSpanFull(),
                ]),

            SeoSection::make(),
        ]);
    }
}
