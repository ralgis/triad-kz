<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Singleton site-settings page (one row at `settings.id = 1`).
 *
 * Implemented as a Filament Page rather than a Resource because we don't
 * want index/create/edit listings — just one form that reads/writes the
 * one row.
 */
class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Настройки сайта';

    protected static ?string $navigationLabel = 'Настройки';

    protected static string|null|\UnitEnum $navigationGroup = 'Настройки сайта';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.settings';

    /**
     * Form state, hydrated from Setting::current() on mount().
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $data = Setting::current()->attributesToArray();

        // Seed the 7-row working-hours Repeater with sensible defaults
        // on first load — otherwise it'd render zero rows and the admin
        // would have to manually add each day, which is the exact UX
        // we replaced the freeform Textarea to avoid.
        if (empty($data['working_hours'])) {
            $data['working_hours'] = self::defaultWorkingHours();
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->model(Setting::current())
            ->components([
                Tabs::make('settings')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Бренд')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make()->columns(2)->schema([
                                    TextInput::make('site_name')
                                        ->label('Название сайта')
                                        ->required(),
                                    TextInput::make('site_tagline')
                                        ->label('Слоган / подзаголовок'),
                                    SpatieMediaLibraryFileUpload::make('logo')
                                        ->label('Логотип')
                                        ->collection('logo')
                                        ->disk('public')
                                        ->image()
                                        ->columnSpanFull()
                                        ->helperText('Подготовь файл заранее (~200×80 px). Кропер не используется — на Plesk shared его base64-блобы не доходят до файловой системы.'),
                                ]),
                            ]),

                        Tabs\Tab::make('Контакты')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make()->columns(2)->schema([
                                    TextInput::make('phone')->label('Телефон основной'),
                                    TextInput::make('phone_secondary')->label('Телефон 2'),
                                    TextInput::make('phone_tertiary')->label('Телефон 3'),
                                    TextInput::make('fax')->label('Факс'),
                                    TextInput::make('public_email')
                                        ->label('Email публичный')
                                        ->email(),
                                    TextInput::make('email_recipient')
                                        ->label('Email для заявок (куда падают)')
                                        ->email()
                                        ->required()
                                        ->helperText('Сюда уходят все формы с сайта.'),
                                    Section::make('Адрес офиса')
                                        ->description('Используется на странице контактов и в Schema.org PostalAddress (важно для local-SEO).')
                                        ->columnSpanFull()
                                        ->columns(4)
                                        ->schema([
                                            TextInput::make('address')
                                                ->label('Улица, дом, корпус, офис')
                                                ->columnSpan(4)
                                                ->placeholder('ул. Бродского, 186'),
                                            TextInput::make('postal_code')
                                                ->label('Индекс')
                                                ->placeholder('050000')
                                                ->maxLength(20),
                                            TextInput::make('city')
                                                ->label('Город')
                                                ->placeholder('Алматы')
                                                ->maxLength(120)
                                                ->columnSpan(2),
                                            TextInput::make('country_code')
                                                ->label('Страна (ISO-код)')
                                                ->disabled()
                                                ->dehydrated()
                                                ->default('KZ')
                                                ->helperText('Защищено от случайной правки. Меняется в коде формы при необходимости.'),
                                        ]),
                                    Section::make('Время работы')
                                        ->description('Расписание по дням недели. Выключи переключатель если день — выходной.')
                                        ->columnSpanFull()
                                        ->schema([
                                            Repeater::make('working_hours')
                                                ->hiddenLabel()
                                                ->columns(4)
                                                ->schema([
                                                    Hidden::make('day'),
                                                    Placeholder::make('day_label')
                                                        ->hiddenLabel()
                                                        ->content(fn ($get): string => Setting::DAYS[$get('day')] ?? '?'),
                                                    Toggle::make('is_open')
                                                        ->label('Работаем')
                                                        ->inline(false)
                                                        ->default(true)
                                                        ->live(),
                                                    TimePicker::make('from')
                                                        ->label('Открытие')
                                                        ->seconds(false)
                                                        ->default('09:00')
                                                        ->disabled(fn ($get) => ! $get('is_open')),
                                                    TimePicker::make('to')
                                                        ->label('Закрытие')
                                                        ->seconds(false)
                                                        ->default('18:00')
                                                        ->disabled(fn ($get) => ! $get('is_open')),
                                                ])
                                                ->default(self::defaultWorkingHours())
                                                ->addable(false)
                                                ->deletable(false)
                                                ->reorderable(false),
                                            // itemLabel intentionally not set — the Placeholder
                                            // inside the row carries the day name now, so the
                                            // collapsible header bar is unnecessary chrome.
                                        ]),

                                    Section::make('Особые дни')
                                        ->description('Праздники, переносы, короткие дни. Если дата не указана — действует обычное недельное расписание.')
                                        ->columnSpanFull()
                                        ->collapsed()
                                        ->schema([
                                            Repeater::make('special_days')
                                                ->label('')
                                                ->columns(4)
                                                ->schema([
                                                    DatePicker::make('date')
                                                        ->label('Дата')
                                                        ->displayFormat('d.m.Y')
                                                        ->required(),
                                                    Select::make('status')
                                                        ->label('Статус')
                                                        ->options([
                                                            'closed' => 'Выходной',
                                                            'short' => 'Короткий день',
                                                        ])
                                                        ->default('closed')
                                                        ->live(),
                                                    TimePicker::make('from')
                                                        ->label('Открытие')
                                                        ->seconds(false)
                                                        ->visible(fn ($get) => $get('status') === 'short'),
                                                    TimePicker::make('to')
                                                        ->label('Закрытие')
                                                        ->seconds(false)
                                                        ->visible(fn ($get) => $get('status') === 'short'),
                                                    TextInput::make('note')
                                                        ->label('Заметка (показывается рядом с датой)')
                                                        ->placeholder('Например: Новый год, Наурыз, корпоратив')
                                                        ->columnSpanFull(),
                                                ])
                                                ->itemLabel(fn (array $state): ?string => isset($state['date'])
                                                    ? $state['date'].($state['note'] ?? '' ? ' — '.$state['note'] : '')
                                                    : null)
                                                ->collapsed()
                                                ->reorderable(false)
                                                ->defaultItems(0)
                                                ->addActionLabel('+ Добавить особый день'),
                                        ]),

                                    ViewField::make('map')
                                        ->view('filament.forms.leaflet-map-picker')
                                        ->columnSpanFull(),
                                    TextInput::make('map_lat')
                                        ->label('Широта (lat)')
                                        ->numeric()
                                        ->step('0.000001'),
                                    TextInput::make('map_lng')
                                        ->label('Долгота (lng)')
                                        ->numeric()
                                        ->step('0.000001'),
                                ]),
                            ]),

                        Tabs\Tab::make('Реквизиты')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Реквизиты для счёта')
                                    ->description('Подставляются в PDF-счёт при безналичном расчёте.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('company_legal_name')
                                            ->label('Юридическое наименование')
                                            ->columnSpanFull(),
                                        TextInput::make('company_bin')
                                            ->label('БИН')
                                            ->maxLength(12)
                                            ->helperText('12 цифр'),
                                        TextInput::make('company_iik')
                                            ->label('ИИК (расчётный счёт)'),
                                        TextInput::make('company_bank')
                                            ->label('Банк (БВУ)')
                                            ->columnSpanFull(),
                                        TextInput::make('company_bik')
                                            ->label('БИК')
                                            ->maxLength(10),
                                        TextInput::make('company_kbe')
                                            ->label('КБЕ')
                                            ->maxLength(3),
                                        TextInput::make('company_legal_address')
                                            ->label('Юридический адрес')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('SEO + Аналитика')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make()->columns(2)->schema([
                                    SpatieMediaLibraryFileUpload::make('og_default')
                                        ->label('OG-картинка по умолчанию')
                                        ->collection('og_default')
                                        ->disk('public')
                                        ->image()
                                        ->columnSpanFull()
                                        ->helperText('Подготовь файл 1200×630 px заранее.'),
                                    Textarea::make('schema_org_organization')
                                        ->label('Schema.org Organization (JSON-LD override)')
                                        ->rows(8)
                                        ->columnSpanFull()
                                        ->helperText('Опциональный JSON для тонкой настройки. Пусто = автогенерация из остальных полей.')
                                        ->formatStateUsing(fn ($state) => is_array($state)
                                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                            : (string) $state)
                                        ->dehydrateStateUsing(fn ($state) => blank($state)
                                            ? null
                                            : json_decode((string) $state, associative: true)),
                                    TextInput::make('analytics_yandex_id')
                                        ->label('Яндекс.Метрика — Counter ID')
                                        ->placeholder('38595400')
                                        ->maxLength(20)
                                        ->helperText('Только цифры. Найти можно в metrika.yandex.ru → ваш счётчик → Настройки. Скрипт счётчика рендерится только в production-окружении.'),

                                    TextInput::make('analytics_google_id')
                                        ->label('Google Analytics 4 — Measurement ID')
                                        ->placeholder('G-XXXXXXXXXX')
                                        ->maxLength(20)
                                        ->helperText('Формат G-XXXXXXXX (GA4). Старый Universal Analytics (UA-…) отключен Google\'ом с 01.07.2023 — не работает, можно не вписывать. Скрипт рендерится только в production.'),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $setting = Setting::current();
        $setting->fill($this->form->getState());
        $setting->save();

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }

    /**
     * 7 rows in the Mon→Sun order with sensible defaults — Mon–Fri
     * 09:00-18:00, weekends closed. Used when the admin opens the
     * form for the first time and the JSON column is empty.
     *
     * @return list<array{day:string, is_open:bool, from:?string, to:?string}>
     */
    private static function defaultWorkingHours(): array
    {
        return [
            ['day' => 'mon', 'is_open' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'tue', 'is_open' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'wed', 'is_open' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'thu', 'is_open' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'fri', 'is_open' => true, 'from' => '09:00', 'to' => '18:00'],
            ['day' => 'sat', 'is_open' => false, 'from' => null, 'to' => null],
            ['day' => 'sun', 'is_open' => false, 'from' => null, 'to' => null],
        ];
    }
}
