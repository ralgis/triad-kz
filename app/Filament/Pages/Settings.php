<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
        $this->form->fill(Setting::current()->attributesToArray());
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
                                        ->image()
                                        ->imageEditor()
                                        ->columnSpanFull(),
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
                                    TextInput::make('address')
                                        ->label('Адрес офиса')
                                        ->columnSpanFull(),
                                    TextInput::make('working_hours')->label('Время работы'),
                                    TextInput::make('skype')->label('Skype'),
                                    TextInput::make('map_lat')->label('Широта (для карты)')->numeric(),
                                    TextInput::make('map_lng')->label('Долгота (для карты)')->numeric(),
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
                                        ->image()
                                        ->imageEditor()
                                        ->columnSpanFull(),
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
                                        ->label('Яндекс.Метрика ID'),
                                    TextInput::make('analytics_google_id')
                                        ->label('Google Analytics ID'),
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
}
