# CLAUDE.md — triad-kz

> Корпоративный B2B-сайт-каталог железобетонных изделий ТОО «ТРИ АД
> Construction» (Алматы). Полный custom-rebuild со старого WordPress 4.3
> (см. `_legacy/` локально или git-history родительского старого сайта).
>
> Это корневой CLAUDE.md с project-wide контекстом. Специфика подсистем —
> в subfolder-файлах (см. §11 «Навигация»).

## 1. ОБЗОР ПРОЕКТА

**Что это:** custom Laravel 11 приложение с админ-панелью на Filament 3.
Каталог из ~38 ЖБИ-изделий, страницы блога, статические страницы, форма
заявок, корзина + checkout без онлайн-оплаты (B2B-флоу: «Безналичный расчёт»
со счёт-фактурой / «Наличный расчёт»).

**Кто пользуется:**
- **Заказчик / контент-менеджер** — Filament-админка по `/admin`. Управляет
  товарами, категориями, статьями, страницами, заказами, заявками,
  настройками, меню.
- **B2B-клиенты** — публичный сайт. Просматривают каталог, оставляют
  заявки или оформляют заказ со счёт-фактурой.

**Ключевые особенности (а не общий e-commerce):**
- Каталог — узкая номенклатура ЖБИ (~38 SKU), не Amazon
- Нет онлайн-оплаты. Заказ → счёт на email клиенту → клиент платит по реквизитам
- На каждом товаре **два изображения**: чертёж (со схемой/размерами) и
  реальное фото изделия. На фронте — переключатель Чертёж/Фото
- Заявки идут на admin-email через простую форму (быстрый запрос цены)
  ИЛИ через checkout-форму корзины (полноценный заказ)
- Никаких регистраций / личного кабинета. Чек-аут как гость
- Mobile-first: вёрстка от 360px и растёт через breakpoints
- SEO критически важен — это **главный канал привлечения**. Все мета,
  schema.org, canonical, sitemap, 301-redirects — first-class

## 2. ТЕХНИЧЕСКИЙ СТЕК

| Слой | Технология | Где конфиг |
|---|---|---|
| Framework | Laravel 11 (LTS до 2026-08) | `composer.json` |
| PHP | 8.4 | `.env`, `composer.json#require.php` |
| DB | MariaDB 10.6 / MySQL 8 | `config/database.php`, `.env` |
| Admin panel | Filament 3 | `app/Filament/`, `config/filament.php` |
| Frontend | Blade + Tailwind 3 + Alpine.js | `resources/views/`, `tailwind.config.js` |
| WYSIWYG | TipTap (через Filament) | используется в Resources |
| Media | spatie/laravel-medialibrary | Models с `InteractsWithMedia` |
| PDF (счёт-фактура) | barryvdh/laravel-dompdf | `App\Services\InvoiceGenerator` |
| 301-redirects | spatie/laravel-missing-page-redirector | `bootstrap/app.php` middleware |
| Sitemap | spatie/laravel-sitemap | `App\Http\Controllers\SitemapController` |
| Security headers | spatie/laravel-csp | `config/csp.php` |
| Auto-backup | spatie/laravel-backup | `config/backup.php` |
| Honeypot | spatie/laravel-honeypot | На всех публичных формах |
| Static analysis | larastan (PHPStan + Laravel) | `phpstan.neon` |
| Formatter | Laravel Pint | `pint.json` |
| Testing | Pest 2 | `tests/Pest.php`, `phpunit.xml` |
| Local dev | Laravel Sail (Docker) | `docker-compose.yml` |
| CI | GitHub Actions | `.github/workflows/ci.yml` |
| Deploy | Plesk Git + post-deploy hook | Plesk панель |

## 3. БЫСТРЫЙ СТАРТ (local dev)

```bash
# Первый старт
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev   # отдельным окном

# Открыть: http://localhost
# Admin: http://localhost/admin (логин из DatabaseSeeder)
```

**Стандартные команды (используем через sail):**
```bash
sail artisan ...              # любая artisan-команда
sail artisan tinker           # REPL
sail artisan make:filament-resource Product  # генератор Filament
sail composer require ...     # composer
sail npm run dev | build      # Vite
sail test                     # phpunit-обёртка (Pest)
sail bin pint                 # форматер
sail bin phpstan analyse      # static-анализ
sail mysql                    # mysql-консоль
sail shell                    # bash внутри app-контейнера
```

**Если sail не запускается** — Docker Desktop не стартован или WSL2 не
поднят. Проверь `docker ps`.

## 4. СТРУКТУРА ПРОЕКТА

```
triad-kz/
├── app/
│   ├── Filament/           # Admin-панель (Resources, Pages, Widgets)
│   │   └── Resources/      # CRUD-ресурсы для каждой модели
│   ├── Http/
│   │   ├── Controllers/    # Публичные controller'ы (Catalog, Cart, Checkout, Article, ...)
│   │   ├── Middleware/     # EnsureNoindexInNonProd, HandleRedirects, ...
│   │   └── Requests/       # FormRequest-классы (валидация)
│   ├── Mail/               # Mailables (OrderConfirmation, NewOrderAdmin, ContactForm)
│   ├── Models/             # Eloquent: Product, Category, Article, Page, Order, Setting, ...
│   ├── Observers/          # Slug auto-redirect observer, Order status logger
│   ├── Providers/          # ServiceProvider'ы
│   ├── Rules/              # Custom validation rules (BinRule, PhoneRule)
│   ├── Services/           # Бизнес-логика (Cart, OrderService, InvoiceGenerator, SeoBuilder)
│   └── Traits/             # HasSeo, переиспользуемые трейты на моделях
├── bootstrap/
├── config/                 # Конфиги пакетов
├── database/
│   ├── factories/          # Model factories для тестов и seed'а
│   ├── migrations/         # См. database/migrations/CLAUDE.md
│   └── seeders/            # DatabaseSeeder (1 admin user + dev fixtures)
├── public/                 # Webroot. Plesk Document Root указывает сюда
├── resources/
│   ├── css/                # Tailwind sources
│   ├── js/                 # Alpine.js + frontend scripts
│   └── views/              # Blade-шаблоны. См. resources/views/CLAUDE.md
├── routes/
│   └── web.php             # Все публичные маршруты
├── storage/
│   └── app/invoices/       # PDF счёт-фактуры (gitignored)
├── tests/
│   ├── Feature/            # End-to-end HTTP-тесты. См. tests/CLAUDE.md
│   ├── Unit/               # Юнит-тесты сервисов и trait'ов
│   └── Pest.php            # Pest config
├── _legacy/                # gitignored: бэкап старого WordPress-сайта + docx
├── .env.example
├── .gitignore
├── CLAUDE.md               # этот файл
├── composer.json
├── docker-compose.yml      # Sail
├── package.json
├── phpstan.neon
├── pint.json
├── tailwind.config.js
└── vite.config.js
```

## 5. КОНВЕНЦИИ КОДА

### PHP

- **PHP 8.4** базово, **`declare(strict_types=1);`** в каждом .php файле
- **PSR-12** через Laravel Pint (`pint.json`)
- **Type hints** на параметры и возвращаемые значения везде
- **Readonly properties** для DTO / Value Objects
- **Final классы** для Services / Controllers (extends только через
  интерфейсы — defensive)
- **Eloquent** для всех БД-операций. Никакого raw SQL с конкатенацией строк
- **FormRequest** для каждого endpoint'а с пользовательским вводом, не
  `$request->validate()` в controller'е
- **Mass assignment**: каждая Model имеет явный `$fillable`. Если защита
  через `$guarded = []` — комментарий «почему ОК»

### Именование

- **Slug** — латиница, kebab-case. Авто-генерация из name через
  spatie/laravel-sluggable
- **Файлы Filament Resources** — единственное число: `ProductResource`,
  `CategoryResource`
- **Тесты** — `tests/Feature/HomePageTest.php` (Feature-test), 
  `tests/Unit/CartTest.php` (Unit-test). Pest-стиль:
  `it('renders home with featured products', ...)`
- **Migrations** — Laravel default `YYYY_MM_DD_HHMMSS_what.php`

### Комментарии

- **Только «почему», не «что»** — имена должны делать «что» очевидным
- **Бизнес-правила** обязательно комментировать с указанием почему именно
  так (см. примеры в `app/Rules/BinRule.php`, `app/Services/Cart.php`)
- **Edge cases** комментировать («корзина пуста при checkout — редирект, не
  throw»)
- **Workaround'ы** только с TODO + GitHub-issue/ссылкой
- **PHPDoc** на public API классов и сервисов (для IDE и phpdoc-генератора)

### Тесты

- Каждый PR должен содержать тесты. **`vendor/bin/pest` чистый** = условие
  merge'а
- Smoke-тест на каждый публичный controller (status 200 + ключевые
  элементы)
- Полный feature-тест на checkout flow (add → cart → checkout → order → pdf
  → mail)
- Unit-тесты на критичные сервисы (Cart, OrderService, InvoiceGenerator)
- Coverage target ≥70% lines, 100% на критичных services

## 6. SECURITY BASELINE

Никаких компромиссов:

- **CSRF** — Laravel default + `@csrf` в формах
- **XSS** — Blade `{{ }}` всегда escape; `{!! !!}` **только** для
  sanitized WYSIWYG output (проверка sanitize через HTMLPurifier на
  сохранении в БД)
- **SQL injection** — только Eloquent
- **Mass assignment** — явные `$fillable`
- **Validation** — `FormRequest` классы
- **Authorization** — Filament Policies на каждую модель в админке
- **Rate limit** — `/checkout/submit` (5/мин/IP), `/contact/submit`
  (3/мин/IP), `/admin/login` (5/мин/IP)
- **Honeypot** — на всех публичных формах (spatie/laravel-honeypot)
- **Session** — `secure=true`, `http_only=true`, `same_site=lax`, driver =
  database
- **CSP** — spatie/laravel-csp, strict policy
- **Security headers** — X-Frame-Options=SAMEORIGIN, X-Content-Type-Options=
  nosniff, Strict-Transport-Security, Referrer-Policy=same-origin
- **`.env`** — gitignored. На сервере через Plesk File Manager
- **`APP_DEBUG=false`** — на dev И prod. Debug-pages выдают пути
- **PII в логах** — НЕ логируем телефоны/имена в info-канал. debug-канал
  с rotation 7 дней
- **Backups** — spatie/laravel-backup, daily, БД + media
- **Dependencies** — `composer audit` в CI, обновления раз в 2 недели

## 7. STABILITY И ERROR-HANDLING

- **Transactions** для бизнес-операций: создание Order + OrderItems в одной
  `DB::transaction()`
- **Try-catch** в external integrations (email send, PDF gen) с logging и
  graceful degradation. Заказ создаётся даже если письмо не ушло — админ
  видит флаг `notification_sent=false` и переотправляет
- **Idempotency** для checkout — повторный submit одной формы не создаёт
  дубль (session + rate-limit)
- **Soft deletes** на Product / Article / Page / Category — нет потери
  данных при случайном удалении в админке
- **Slug-stability** — observer auto-redirect защищает SEO даже при ручной
  правке slug в админке
- **Никаких `except Exception: pass`** в коде — всегда логируем перед
  re-throw или silent-skip

## 8. ENVIRONMENTS

| Env | Где | APP_ENV | APP_DEBUG | HTTP basic auth |
|---|---|---|---|---|
| Local | Docker Sail | `local` | `true` (только локально) | — |
| Dev (Plesk) | `dev.triad.kz` | `dev` | `false` | **Да** (доступ только по логин/пароль) |
| Production | `triad.kz` (после cutover) | `production` | `false` | — |

**dev защищён от индексации в 4 слоя:**
1. HTTP basic auth в Plesk
2. Middleware `EnsureNoindexInNonProd` → `X-Robots-Tag: noindex`
3. `<meta name="robots" content="noindex">` в layout (условно)
4. `robots.txt` → `Disallow: /` (controller-generated, env-aware)

Все три ($2-4) деактивируются автоматически при `APP_ENV=production`.

## 9. WORKFLOW (Git + deploy)

- **Branching:** `main` = prod, feature-ветки `feat/...`, fix-ветки
  `fix/...`
- **Commits:** Conventional Commits — `feat:`, `fix:`, `refactor:`,
  `docs:`, `chore:`, `test:`. Сообщения на английском
- **PR:** обязательны для всех изменений в `main`. CI должен быть зелёный
- **CI** (`.github/workflows/ci.yml`):
  - `vendor/bin/pint --test`
  - `vendor/bin/phpstan analyse`
  - `vendor/bin/pest`
  - На failing — merge невозможен
- **Push в `main`** триггерит auto-deploy на `dev.triad.kz` через Plesk Git
  + post-deploy hook (composer install, migrate, cache:rebuild)
- **Cutover на triad.kz** — отдельной координированной операцией
  (см. план в `_legacy/` или `~/.claude/plans/swirling-riding-raccoon.md`)

## 10. ЧТО НЕЛЬЗЯ ДЕЛАТЬ

- НЕ удалять `_legacy/` — там бэкап старого сайта и docx-план заказчика
  (gitignored, лежит локально)
- НЕ коммитить `.env`, `auth.json`, `_legacy/`, `план_работ_*`
- НЕ менять `db/init.sql` (его нет, всё через Laravel migrations)
- НЕ использовать raw SQL с конкатенацией
- НЕ скрывать exceptions молчанием (`except Exception: pass`-аналог в PHP)
- НЕ ссылаться в коде на задачи/коммиты/PR — это decay'ит, эту инфу
  держим в PR-описаниях
- НЕ комментировать «что делает код» — имя функции уже это говорит. Только
  «почему» и edge-cases

## 11. НАВИГАЦИЯ

Подсистемные CLAUDE.md загружаются кумулятивно при работе в соответствующем
subfolder'е. Начинай с них когда контекст работы уже ограничен конкретной
подсистемой.

| Путь | Что там |
|---|---|
| [app/Filament/CLAUDE.md](app/Filament/CLAUDE.md) | Конвенции админ-панели: паттерн Resource с SEO-блоком, переиспользование `getSeoSchema()`, как добавить новую сущность |
| [resources/views/CLAUDE.md](resources/views/CLAUDE.md) | Blade-конвенции: layout-inheritance, partials, SEO meta, mobile-first breakpoints |
| [tests/CLAUDE.md](tests/CLAUDE.md) | Test-конвенции: Feature vs Unit, naming, factories, мокинг |
| [database/migrations/CLAUDE.md](database/migrations/CLAUDE.md) | Migration-конвенции: timestamps, FK, индексы, как добавлять SEO-поля |

## 12. ПОЛЕЗНЫЕ ССЫЛКИ

- Старый сайт: https://triad.kz (на момент рерайта — WP 4.3 + GoodStore)
- Dev-инстанс (после cutover): https://dev.triad.kz
- Plesk панель: ps-cloud-services (заказчик предоставляет креды)
- GitHub: https://github.com/ralgis/triad-kz
- План работ (docx → md): `_legacy/план_работ_triad.md`
- План рерайта: `~/.claude/plans/swirling-riding-raccoon.md`
