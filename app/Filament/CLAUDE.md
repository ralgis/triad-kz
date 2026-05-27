# CLAUDE.md — app/Filament/

> Filament v5 admin panel. Это **не готовая CMS** — Filament генерирует CRUD из
> моделей и форм. У нас — каталог ЖБИ + статьи + страницы + заказы +
> настройки. Заказчик видит `/admin` после логина и редактирует контент.

## 1. ОСНОВНЫЕ КОНЦЕПЦИИ FILAMENT 5

- **Panel** (`app/Providers/Filament/AdminPanelProvider.php`) — корневой
  компонент админки. Регистрирует Resources, страницы, виджеты, темы,
  плагины. У нас один панель — `admin`
- **Resource** (`app/Filament/Resources/<Model>Resource.php`) — CRUD-описание
  одной Eloquent-модели. Содержит:
  - `form()` — поля редактора (Forms\Components\\*)
  - `table()` — список (Tables\Columns\\*)
  - Pages: ListRecords, CreateRecord, EditRecord
- **Pages** — кастомные страницы вне Resource-CRUD (Dashboard, Settings и т.д.)
- **Widgets** — карточки на Dashboard

## 2. RESOURCE-ПАТТЕРН ДЛЯ ЭТОГО ПРОЕКТА

У нас 4 контент-модели (`Category`, `Product`, `Article`, `Page`) делят
одинаковую SEO-секцию. Реализация:

### Reusable SEO-секция

`app/Filament/Components/SeoSection.php` — статическая функция возвращающая
массив компонентов для `Section::make('SEO')->schema([...])`:

```php
public static function make(): Section
{
    return Section::make('SEO')
        ->collapsed()
        ->columns(2)
        ->schema([
            TextInput::make('meta_title')
                ->maxLength(60)
                ->hint('50-60 символов')
                ->helperText(/* … */),
            Textarea::make('meta_description')
                ->maxLength(160)
                ->hint('150-160 символов'),
            FileUpload::make('og_image')
                ->image()
                ->imageEditor()
                ->directory('og-images'),
            TextInput::make('canonical_url')
                ->url()
                ->placeholder('Оставить пустым = автоматический'),
            Toggle::make('noindex')
                ->helperText('Включить — страница не попадёт в Google'),
        ]);
}
```

В Resource'ах используется так:

```php
public function form(Schema $schema): Schema
{
    return $schema->components([
        Section::make('Контент')->schema([/* поля модели */]),
        SeoSection::make(),  // ← переиспользуемая секция
    ]);
}
```

### TipTap WYSIWYG c кастомной перелинковкой

WYSIWYG-редакторы используют встроенный TipTap. Кастомная команда «Вставить
ссылку на товар/статью» реализуется через:

```php
RichEditor::make('description')
    ->required()
    ->toolbarButtons([
        'bold', 'italic', 'strike', 'link',
        'h2', 'h3', 'bulletList', 'orderedList',
        'blockquote', 'codeBlock', 'attachFiles',
        'internalLink',  // ← наш кастомный
    ])
    ->plugins([InternalLinkPlugin::make()]);
```

Реализация `InternalLinkPlugin` в `app/Filament/Plugins/InternalLinkPlugin.php`.

### Media (фото товара + чертёж)

Через Spatie Medialibrary + Filament SpatieMediaLibraryFileUpload:

```php
SpatieMediaLibraryFileUpload::make('image_blueprint')
    ->collection('blueprint')        // одна картинка-чертёж
    ->image()
    ->imageEditor()
    ->required(false),

SpatieMediaLibraryFileUpload::make('image_real')
    ->collection('real')             // реальное фото изделия
    ->image()
    ->imageEditor()
    ->required(false),

SpatieMediaLibraryFileUpload::make('gallery')
    ->collection('gallery')          // допфото — массив
    ->multiple()
    ->reorderable()
    ->image()
    ->imageEditor(),
```

## 3. КАК ДОБАВИТЬ НОВУЮ СУЩНОСТЬ В АДМИНКУ

1. **Создать модель + миграцию:**
   ```bash
   php artisan make:model Thing -m
   ```
2. **Описать миграцию** (см. `database/migrations/CLAUDE.md`)
3. **Добавить traits** в модель:
   ```php
   use App\Traits\HasSeo;            // только если контент-сущность
   use Spatie\Sluggable\HasSlug;     // если есть URL
   use Spatie\Sluggable\SlugOptions;
   use Spatie\MediaLibrary\HasMedia;
   use Spatie\MediaLibrary\InteractsWithMedia;
   ```
4. **Сгенерировать Resource:**
   ```bash
   php artisan make:filament-resource Thing --generate
   ```
5. **Открыть `app/Filament/Resources/ThingResource.php`** и:
   - Добавить `SeoSection::make()` в `form()`
   - Настроить колонки в `table()`
   - Включить фильтры, search, sort
   - Добавить Media-поля если есть
6. **Добавить Policy** (`app/Policies/ThingPolicy.php`) — даже если
   политики простые (`return true` для админа), это явный security gate

## 4. ROUTE / URL АДМИНКИ

- `/admin` — login или dashboard
- `/admin/login` — форма
- `/admin/{resource}` — список (ListRecords)
- `/admin/{resource}/create` — новая запись
- `/admin/{resource}/{id}/edit` — редактирование

URL префикс задаётся в `AdminPanelProvider::path('admin')`. Не меняем без
причины — заказчику привычно `/admin`.

## 5. КАСТОМИЗАЦИИ Я ИНОГДА ПИШУ

### Singleton Settings — не Resource, а Page

`Setting` — это singleton, не коллекция. Используем кастомную страницу
вместо Resource'а. См. `app/Filament/Pages/Settings.php`.

### Order workflow — bulk actions

В `OrderResource::table()`:

```php
->bulkActions([
    BulkActionGroup::make([
        Action::make('confirm')
            ->label('Подтвердить')
            ->action(fn ($records) => /* update status */)
            ->requiresConfirmation(),
        Action::make('markPaid'),
        Action::make('markShipped'),
        Action::make('cancel'),
    ]),
])
```

Каждое действие пишет в `status_history` JSON-поле (через Observer на Order).

## 6. ТЕСТЫ FILAMENT

Filament 5 имеет `livewire-testing` адаптер. Тесты CRUD-операций:

```php
use function Pest\Livewire\livewire;

it('creates a product through admin', function () {
    $admin = User::factory()->create();

    livewire(\App\Filament\Resources\ProductResource\Pages\CreateRecord::class)
        ->actingAs($admin)
        ->fillForm([
            'name' => 'Кольцо КС15',
            'sku' => 'KS-15',
            'price' => 5000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(\App\Models\Product::where('sku', 'KS-15')->exists())->toBeTrue();
});
```

## 7. ЧТО НЕЛЬЗЯ ДЕЛАТЬ

- НЕ создавать Resource без явной Policy. Default Filament-policy = deny.
  Без policy сущность не появится в навигации
- НЕ хранить логику в Resource — модели и сервисы делают логику. Resource
  только описывает форму и таблицу
- НЕ ставить Filament-плагины из ThirdParty без проверки maintainer'а —
  Filament-экосистема молодая, multitude abandoned packages

## 8. ПОЛЕЗНЫЕ КОМАНДЫ

```bash
php artisan make:filament-resource Product --generate
php artisan make:filament-user                    # создать админа
php artisan filament:upgrade                       # после composer update
php artisan filament:optimize                      # production cache
php artisan filament:cache-components              # ускоряет prod
```

## 9. ДОКУМЕНТАЦИЯ

- https://filamentphp.com/docs/5.x — основа
- https://filamentphp.com/plugins — плагины
- TipTap (наш WYSIWYG): https://tiptap.dev/docs
