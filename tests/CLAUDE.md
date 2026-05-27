# CLAUDE.md — tests/

> Pest 4 — современный синтаксис поверх PHPUnit. Тесты пишутся **вместе с
> кодом**, не «потом». PR без тестов не мержится — это условие CI.

## 1. ЗАПУСК

```bash
php artisan test                # Laravel wrapper
vendor/bin/pest                 # напрямую Pest
vendor/bin/pest --filter=Cart   # один тест
vendor/bin/pest --coverage      # с покрытием (медленно)
vendor/bin/pest --parallel      # параллельно (быстрее)
```

## 2. СТРУКТУРА

```
tests/
├── Pest.php           # конфиг: какие TestCase используются по умолчанию
├── TestCase.php       # базовый класс (extends Laravel TestCase)
├── Feature/           # HTTP / интеграционные тесты
│   ├── HomePageTest.php
│   ├── CatalogTest.php
│   ├── ProductPageTest.php
│   ├── CartTest.php
│   ├── CheckoutFlowTest.php
│   ├── AdminCrudTest.php
│   ├── SitemapTest.php
│   ├── RedirectsTest.php
│   └── DevNoindexTest.php
└── Unit/              # юнит-тесты сервисов и trait'ов
    ├── CartServiceTest.php
    ├── OrderServiceTest.php
    ├── InvoiceGeneratorTest.php
    ├── HasSeoTraitTest.php
    ├── BinRuleTest.php
    └── SlugObserverTest.php
```

## 3. ПРАВИЛО: FEATURE vs UNIT

**Feature** — тест проходит через HTTP-стек / БД / реальные сервисы:
```php
it('renders catalog page with featured products', function () {
    Product::factory()->count(3)->featured()->create();

    $response = $this->get('/catalog/');

    $response->assertOk()
        ->assertSee('Каталог')
        ->assertViewHas('products');
});
```

**Unit** — тест изолированный, без HTTP/DB (используем mock'и):
```php
it('Cart::add increments existing item qty', function () {
    $cart = new Cart;
    $cart->add(productId: 1, qty: 2);
    $cart->add(productId: 1, qty: 3);

    expect($cart->items()[1]->qty)->toBe(5);
});
```

**Когда что:**
- Тестируешь controller / endpoint / view — Feature
- Тестируешь сервис / trait / правило валидации — Unit
- Тестируешь Filament-action — Feature через livewire-testing
- Тестируешь Mailable / PDF generation — Unit с `Mail::fake()` / disk mock

## 4. ИСПОЛЬЗОВАНИЕ DB

`tests/Pest.php`:

```php
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(\Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');
```

`RefreshDatabase` пересоздаёт БД для каждого Feature-теста. Это медленнее
чем `DatabaseTransactions`, но защищает от data-leak между тестами.

**Pest 4 features** — testing-DB driver = SQLite in-memory (быстрее
дискового). В `phpunit.xml`:

```xml
<server name="DB_CONNECTION" value="sqlite"/>
<server name="DB_DATABASE" value=":memory:"/>
```

## 5. FACTORIES

Каждая модель имеет factory в `database/factories/<Model>Factory.php`.
Используется в тестах:

```php
$product = Product::factory()->create([
    'name' => 'Кольцо КС15',
    'sku' => 'KS-15-90',
]);

$category = Category::factory()->withProducts(3)->create();

$order = Order::factory()->bankTransfer()->confirmed()->create();
```

Кастомные states описываются методами на Factory-классе:

```php
class ProductFactory extends Factory
{
    public function featured(): static
    {
        return $this->state(['featured' => true]);
    }

    public function withPrice(int $price): static
    {
        return $this->state(['price' => $price, 'price_visible' => true]);
    }
}
```

## 6. МОКИ И ФЕЙКИ

```php
// Mail
Mail::fake();
// ... action that sends mail
Mail::assertSent(OrderConfirmationMail::class, fn ($m) => $m->order->id === 1);

// Notifications
Notification::fake();

// Queue
Queue::fake();
Queue::assertPushed(GenerateInvoiceJob::class);

// Storage
Storage::fake('local');
Storage::disk('local')->assertExists('invoices/T-000001.pdf');

// HTTP
Http::fake(['api.example.com/*' => Http::response(['ok' => true])]);
```

## 7. CHECKOUT FLOW — END-TO-END

Это самый важный feature-тест в проекте:

```php
it('completes legal-entity checkout with bank transfer invoice', function () {
    Mail::fake();
    Storage::fake('local');

    $product = Product::factory()->withPrice(5000)->create(['sku' => 'KS-15']);

    // Add to cart
    $this->post('/cart/add', ['product_id' => $product->id, 'qty' => 4])
        ->assertRedirect('/cart');

    // Submit checkout
    $response = $this->post('/checkout/submit', [
        'customer_type' => 'legal',
        'customer_company_name' => 'ТОО Тестовая компания',
        'customer_bin' => '123456789012',
        'customer_name' => 'Иванов Иван',
        'customer_email' => 'test@example.com',
        'customer_phone' => '+77001234567',
        'customer_address' => 'Алматы, ул. Тестовая 1',
        'delivery_method' => 'pickup',
        'payment_method' => 'bank_transfer',
    ]);

    $order = Order::latest()->first();
    expect($order)->not->toBeNull();
    expect($order->total)->toBe(20000.0);
    expect($order->status)->toBe('new');

    Mail::assertSent(OrderConfirmationMail::class);
    Mail::assertSent(NewOrderAdminMail::class);
    Storage::disk('local')->assertExists("invoices/{$order->order_number}.pdf");

    $response->assertRedirect("/order/{$order->order_number}");
});
```

## 8. FILAMENT-ТЕСТЫ

Используем livewire-testing адаптер Filament:

```php
use function Pest\Livewire\livewire;

it('admin creates product through filament resource', function () {
    $admin = User::factory()->create();

    livewire(\App\Filament\Resources\ProductResource\Pages\CreateRecord::class)
        ->actingAs($admin)
        ->fillForm([
            'name' => 'Test product',
            'sku' => 'TST-1',
            'price' => 1000,
            'price_visible' => true,
            'meta_title' => 'Test product · Triad',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::where('sku', 'TST-1')->exists())->toBeTrue();
});
```

## 9. SECURITY-ТЕСТЫ

- `tests/Feature/DevNoindexTest.php` — `X-Robots-Tag: noindex` на dev,
  отсутствие на production
- `tests/Feature/CsrfTest.php` — POST без CSRF возвращает 419
- `tests/Feature/RateLimitTest.php` — 6-й submit за минуту возвращает 429
- `tests/Feature/AdminAuthTest.php` — `/admin/*` без логина → 302 на login
- `tests/Feature/XssTest.php` — попытка засунуть `<script>` в product
  description → sanitize её удаляет

## 10. ПРАВИЛА

- **Тест ОБЯЗАТЕЛЕН для каждого нового controller / service / Filament
  Resource**
- **Имя теста — описание поведения**: `it('redirects to login when not authenticated')`,
  не `testLoginRedirect`
- **AAA pattern**: Arrange, Act, Assert. Один тест — одна проверка
- **DRY через `beforeEach()`**, не через копипасту setup'а
- **Не тестируй framework** — не пиши `it('eloquent saves data')` (Laravel
  это уже тестирует)
- **Coverage target**: ≥70% lines всего проекта, **100% на критичных
  services** (Cart, OrderService, InvoiceGenerator)
- **PR с упавшими тестами не мержится** — CI блокирует

## 11. ПОЛЕЗНЫЕ КОМАНДЫ

```bash
vendor/bin/pest --filter="checkout"           # запустить только подходящие
vendor/bin/pest --group=billing                # запустить группу
vendor/bin/pest --dirty                        # только changed files
vendor/bin/pest --bail                         # стоп на первом fail
vendor/bin/pest --coverage --min=70            # упасть если coverage < 70%
vendor/bin/pest --parallel --processes=4       # параллельно
```
