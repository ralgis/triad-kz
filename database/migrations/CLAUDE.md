# CLAUDE.md — database/migrations/

> Laravel-миграции — единственный способ менять схему БД. Никаких ALTER TABLE
> руками. Никаких SQL-дампов с патчами. Прошло время WordPress.

## 1. БАЗОВЫЕ ПРАВИЛА

- Создаём миграцию: `php artisan make:migration create_<table>_table`
- Создаём миграцию для изменения: `php artisan make:migration add_<field>_to_<table>_table`
- Имя файла Laravel генерирует с timestamp — не переименовывать
- Каждая миграция должна иметь **обратимый `down()`**
- Все таблицы имеют `id()`, `timestamps()`
- Soft-deleted модели имеют `softDeletes()` (создаёт колонку `deleted_at`)

## 2. ОБЩИЕ ШАБЛОНЫ

### Контент-таблица с SEO

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();

    // ↓ контент-поля
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('sku')->unique();
    $table->string('gost')->nullable();
    $table->json('dimensions')->nullable();
    $table->decimal('weight_kg', 8, 2)->nullable();
    $table->decimal('price', 12, 2)->nullable();
    $table->string('price_unit')->default('шт');
    $table->boolean('price_visible')->default(false);
    $table->string('unit_for_order')->default('шт');
    $table->longText('description')->nullable();
    $table->boolean('published')->default(false);
    $table->boolean('featured')->default(false);
    $table->boolean('in_stock')->default(true);

    // ↓ SEO-поля (используются HasSeo trait'ом на модели)
    $table->string('meta_title')->nullable();
    $table->string('meta_description', 500)->nullable();
    $table->string('canonical_url')->nullable();
    $table->boolean('noindex')->default(false);
    $table->json('structured_data_override')->nullable();

    // ↓ housekeeping
    $table->softDeletes();
    $table->timestamps();

    // ↓ индексы
    $table->index('published');
    $table->index('featured');
    $table->index(['published', 'featured']);
});
```

### Foreign key — c onDelete

```php
$table->foreignId('category_id')
    ->constrained()
    ->onDelete('restrict');   // ← запретить удаление категории если есть товары

// vs

$table->foreignId('order_id')
    ->constrained()
    ->onDelete('cascade');    // ← удалить order_items если удалён order
```

**Когда какой `onDelete`:**
- `restrict` (или `noAction`) — для критичных связей (Category → Product,
  Order → User). Не даём случайно удалить с потерей данных
- `cascade` — для подчинённых сущностей которые без родителя бессмысленны
  (OrderItem → Order). Удалили заказ — позиции тоже удалились
- `set null` (только если колонка nullable) — для necessary-but-not-critical
  связей. Например, Product удалили → OrderItem.product_id = NULL, но запись
  заказа остаётся

### Polymorphic relations (для MenuItem)

```php
Schema::create('menu_items', function (Blueprint $table) {
    $table->id();
    $table->string('label');
    $table->string('url')->nullable();           // для внешних
    $table->nullableMorphs('linkable');          // создаёт linkable_type + linkable_id
    $table->unsignedInteger('order')->default(0);
    $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
    $table->string('position')->default('header'); // header / footer
    $table->timestamps();

    $table->index(['position', 'order']);
});
```

### M2M (Product ↔ Category)

```php
Schema::create('category_product', function (Blueprint $table) {
    $table->foreignId('category_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('cascade');

    $table->primary(['category_id', 'product_id']);
});
```

Названия pivot-таблиц — **в алфавитном порядке единственного числа**:
`category_product`, не `product_category` или `products_categories`.

## 3. ИНДЕКСЫ

Обязательны:
- На `slug` (uniq) — мы фильтруем по slug каждый запрос
- На FK-колонки — `constrained()` создаёт автоматически
- На `published`, `featured` — частые WHERE-фильтры
- На `published_at` для articles — sort by published_at

Не нужны:
- На `name`, `description` — мы не WHERE по тексту (для full-text см. §6)
- На `created_at`, `updated_at` — Eloquent редко WHERE по ним

## 4. SEO-ПОЛЯ ЧЕРЕЗ TRAIT — НЕ КОПИРУЕМ В КАЖДОЙ МИГРАЦИИ

Эти 5 SEO-полей повторяются на 4 моделях (Category, Product, Article, Page).
Чтобы не копипастить — используем helper:

`database/migrations/Blueprints/SeoFields.php`:

```php
public static function add(Blueprint $table): void
{
    $table->string('meta_title')->nullable();
    $table->string('meta_description', 500)->nullable();
    $table->string('canonical_url')->nullable();
    $table->boolean('noindex')->default(false);
    $table->json('structured_data_override')->nullable();
}
```

В миграциях:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    // ... content fields ...
    SeoFields::add($table);
    $table->timestamps();
});
```

## 5. JSON-ПОЛЯ

Используем для:
- `dimensions` на Product (`{"diameter": 150, "length": 1000, "wall": 80}`)
- `schema_org_organization` на Setting (`{"name": "...", "address": {...}}`)
- `status_history` на Order (массив объектов смены статуса)

```php
$table->json('dimensions')->nullable();
```

На модели — cast:

```php
protected $casts = [
    'dimensions' => 'array',
    'status_history' => 'array',
];
```

## 6. FULL-TEXT SEARCH

Для каталога ЖБИ (~38 SKU) full-text не нужен — LIKE-запросы хватит.
Если в будущем понадобится — Laravel Scout + meilisearch (отдельный
проект, не сейчас).

## 7. SQLite vs MySQL — НЮАНСЫ

Local dev использует SQLite (`database/database.sqlite`).
CI и production — MariaDB / MySQL.

**Что отличается:**
- SQLite не имеет `ENUM` — используем `string` + enum-cast на модели
- SQLite не имеет встроенного `JSON_*` функций (только в новых версиях)
- SQLite не имеет full-text index'ов (но они нам не нужны)
- SQLite не поддерживает `ALTER TABLE DROP COLUMN` без doctrine/dbal — если
  нужно — поставить `composer require doctrine/dbal`

**Когда писать миграции — пишем кросс-совместимо:**

```php
// ❌ Не используй ->enum() — несовместимо с SQLite
$table->enum('status', ['new', 'paid', 'shipped']);

// ✅ Используй string + cast на модели
$table->string('status')->default('new');
// + on model: protected $casts = ['status' => OrderStatus::class];
```

## 8. SEED'Ы

`database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    User::factory()->create([
        'name' => 'Admin',
        'email' => env('ADMIN_EMAIL', 'admin@triad.kz'),
        'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
    ]);

    if (app()->environment('local')) {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
```

Прод-seed = только админ. Dev-seed = админ + sample-контент.

## 9. КАК ДОБАВИТЬ ПОЛЕ К СУЩЕСТВУЮЩЕЙ ТАБЛИЦЕ

1. `php artisan make:migration add_<field>_to_<table>_table --table=<table>`
2. Открыть migration:
   ```php
   public function up(): void
   {
       Schema::table('products', function (Blueprint $table) {
           $table->boolean('hidden_from_catalog')->default(false)->after('published');
       });
   }

   public function down(): void
   {
       Schema::table('products', function (Blueprint $table) {
           $table->dropColumn('hidden_from_catalog');
       });
   }
   ```
3. `php artisan migrate`
4. Добавить поле в `$fillable` (Eloquent)
5. Добавить тест который покрывает новое поле
6. Если поле — bool/boolean — задать `default` всегда

## 10. ЧТО НЕЛЬЗЯ ДЕЛАТЬ

- НЕ менять уже-применённые миграции — создавай новую
- НЕ удалять миграцию без `down()` который реально откатывает
- НЕ использовать `ENUM` columns (несовместимо с SQLite)
- НЕ забывать индексы на FK и `slug`
- НЕ хардкодить timestamps вручную — `timestamps()` хелпер
- НЕ хранить большие BLOB'ы в БД — используй Spatie Medialibrary (файлы
  на диск, метаданные в БД)
- НЕ забывать `softDeletes()` на контент-моделях — заказчик клацнет
  «удалить» и нужен undo

## 11. RUN-команды

```bash
php artisan migrate                  # apply pending
php artisan migrate:status           # show what's applied
php artisan migrate:rollback         # undo last batch
php artisan migrate:rollback --step=3
php artisan migrate:fresh            # drop ALL + re-migrate (dev only!)
php artisan migrate:fresh --seed     # + run seeders
```
