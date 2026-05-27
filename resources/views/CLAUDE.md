# CLAUDE.md — resources/views/

> Blade-шаблоны фронтенда. **Mobile-first** вёрстка через Tailwind CSS v4.
> Все публичные страницы рендерятся server-side (SSR) — это даёт идеальный
> SEO без costs JavaScript-рендеринга.

## 1. СТРУКТУРА ПАПОК

```
resources/views/
├── layouts/
│   └── app.blade.php          # главный layout (header + main + footer)
├── partials/
│   ├── head.blade.php         # <head>: meta, OG, JSON-LD, analytics
│   ├── header.blade.php       # sticky header + меню
│   ├── footer.blade.php       # footer с контактами
│   ├── mobile-menu.blade.php  # выезжающее мобильное меню (Alpine.js)
│   └── schema/
│       ├── organization.blade.php
│       ├── product.blade.php
│       ├── article.blade.php
│       └── breadcrumb.blade.php
├── home.blade.php
├── catalog/
│   ├── index.blade.php        # /catalog/
│   ├── category.blade.php     # /catalog/{cat}/
│   └── product.blade.php      # /catalog/{cat}/{product}/
├── cart.blade.php
├── checkout/
│   ├── form.blade.php
│   └── success.blade.php
├── blog/
│   ├── index.blade.php
│   └── article.blade.php
├── page.blade.php             # универсальный для Page модели
├── contacts.blade.php
├── components/                # переиспользуемые Blade-components
│   ├── input.blade.php
│   ├── select.blade.php
│   ├── textarea.blade.php
│   ├── button.blade.php
│   ├── product-card.blade.php
│   └── breadcrumb.blade.php
├── pdf/
│   └── invoice.blade.php      # счёт-фактура (DomPDF)
└── emails/
    ├── order-confirmation.blade.php
    ├── new-order-admin.blade.php
    └── contact-form.blade.php
```

## 2. MOBILE-FIRST ПРАВИЛА

**Базовая вёрстка пишется под 360px (минимальный мобильник).** Дальше
расширяется через Tailwind breakpoints:

| Breakpoint | Min-width | Что значит |
|---|---|---|
| (default) | 0 | mobile (360-767) |
| `sm:` | 640 | большой телефон / маленький планшет |
| `md:` | 768 | планшет |
| `lg:` | 1024 | desktop |
| `xl:` | 1280 | большой desktop |
| `2xl:` | 1536 | широкий |

**Правильно (mobile-first):**
```html
<div class="flex flex-col md:flex-row gap-4">
    <div class="w-full md:w-1/2">...</div>
</div>
```
- На мобильном: вертикальная колонка, full-width
- На `md+`: горизонтальный flex, половина каждый

**Неправильно (desktop-first):**
```html
<div class="flex flex-row max-md:flex-col">  <!-- не пиши так -->
```

**Каталог-сетка (товары):**
- mobile: `grid-cols-1`
- tablet (`md`): `grid-cols-2`
- desktop (`lg`): `grid-cols-3`

**Категории-плитка:**
- mobile: `grid-cols-2` (2 кнопки в ряд — удобный thumb-tap)
- tablet: `grid-cols-3`
- desktop: `grid-cols-4`

## 3. SEO-META В LAYOUT

`layouts/app.blade.php` принимает данные через `@props([...])` или просто
через переменные из controller'а:

```blade
@extends('layouts.app', [
    'meta_title' => $product->meta_title ?? $product->name . ' · ТРИ АД Construction',
    'meta_description' => $product->meta_description ?? Str::limit(strip_tags($product->description), 160),
    'og_image' => $product->getFirstMediaUrl('real', 'og') ?: asset('og-default.png'),
    'schema_jsonld' => view('partials.schema.product', compact('product'))->render(),
    'breadcrumb' => $breadcrumb,
])
```

В `partials/head.blade.php`:

```blade
<title>{{ $meta_title }}</title>
<meta name="description" content="{{ $meta_description }}">
@if(! app()->environment('production') || ($noindex ?? false))
    <meta name="robots" content="noindex, nofollow">
@endif
<link rel="canonical" href="{{ $canonical_url ?? url()->current() }}">

<meta property="og:title" content="{{ $meta_title }}">
<meta property="og:description" content="{{ $meta_description }}">
<meta property="og:image" content="{{ $og_image }}">
<meta property="og:type" content="{{ $og_type ?? 'website' }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:locale" content="ru_RU">

@isset($schema_jsonld)
    {!! $schema_jsonld !!}
@endisset
```

## 4. БЕЗОПАСНОСТЬ В BLADE

- `{{ $var }}` — **escape по умолчанию**. Используй всегда.
- `{!! $var !!}` — НЕ escape. Используй **только** для:
  - JSON-LD partials (`partials.schema.*`)
  - WYSIWYG output моделей, где content **sanitized на сохранении** через
    `mews/purifier` (см. `app/Observers/SanitizeContentObserver.php`)
  - HTML-партиалы которые мы сами пишем
- НИКОГДА `{!! $user_input !!}` напрямую — XSS
- НИКОГДА не строить URL'ы через конкатенацию строк — `route('cart.add')`
- Csrf-token в формах обязателен: `@csrf`

## 5. ACCESSIBILITY (A11y)

Базовый минимум:
- Семантические теги: `<header>`, `<nav>`, `<main>`, `<article>`,
  `<section>`, `<footer>`. Не `<div>` под всё подряд
- `alt` атрибут на каждом `<img>`. Для декоративных: `alt=""`
- `label[for]` связан с `<input id>`. Или `<label>` оборачивает input
- Focus styles видимы: `focus:ring-2 focus:ring-blue-500 focus:outline-none`
- Контраст текста ≥ AA (WCAG): минимум 4.5:1 для обычного текста
- Skip-to-content link в layout'e: `<a class="sr-only focus:not-sr-only" href="#main">Перейти к контенту</a>`
- Кнопки — `<button>`, не `<div onclick>`. Ссылки — `<a href>`, не
  `<span onclick>`

## 6. РЕДАКТИРУЕМЫЙ КОНТЕНТ ИЗ БД

WYSIWYG-output из БД (поля `description`, `content`):

```blade
{{-- НЕ {{ $product->description }} — это покажет HTML как текст --}}
{{-- НЕ {!! $product->description !!} напрямую — XSS если sanitize упал --}}

{{-- Правильно: метод модели с явным sanitize-fallback --}}
<div class="prose prose-lg max-w-none">
    {!! $product->sanitized_description !!}
</div>
```

Метод `sanitized_description` на модели делает `Purifier::clean()` через
mews/purifier с whitelist allow-list. Это **второй слой защиты** — первый
работает на Observer перед записью в БД.

## 7. TAILWIND v4 — НОВОЕ

Tailwind v4 (вышел 2024-12) использует CSS-конфиг вместо JS:
- Нет `tailwind.config.js` — конфиг в `resources/css/app.css`:
  ```css
  @import "tailwindcss";
  @theme {
      --color-brand: oklch(0.65 0.18 250);
      --font-display: "Inter", sans-serif;
  }
  ```
- Vite-plugin `@tailwindcss/vite` — нативно интегрирован
- Утилиты не изменились: `flex md:grid-cols-2 hover:bg-blue-500` работают
- Кастомные классы через `@layer components` в `app.css`

## 8. ALPINE.JS

Для интерактивности без React:
- Mobile-menu toggle
- Modal'ы (форма заявки на карточке товара)
- Lightbox для галереи
- Tabs «Чертёж / Фото» на продукте
- Form-state (показать поля юрлица если выбран «Юрлицо»)

Базовая структура:
```html
<div x-data="{ open: false }">
    <button @click="open = !open">Меню</button>
    <div x-show="open" x-transition>...</div>
</div>
```

Alpine не подключается отдельно — Livewire (зависимость Filament) уже его
подтягивает. Для публичных страниц без Livewire — импорт в
`resources/js/app.js`.

## 9. КАК ДОБАВИТЬ НОВУЮ СТРАНИЦУ

1. Создать controller-метод (или Page-модель если контент-страница)
2. Добавить route в `routes/web.php`
3. Создать `.blade.php` в правильной подпапке
4. `@extends('layouts.app', [...])` с meta-данными
5. Если URL-структура — создать запись в Sitemap-controller
6. Написать Feature-test (см. `tests/CLAUDE.md`)
7. Проверить mobile (DevTools → responsive 360px)
8. Проверить Lighthouse (Performance + A11y + SEO)

## 10. ЧТО НЕЛЬЗЯ ДЕЛАТЬ

- НЕ инлайнить большие куски CSS в шаблоны — используй Tailwind или
  `resources/css/app.css`
- НЕ использовать `style="..."` атрибуты — CSP может их заблокировать
- НЕ использовать `<script>` инлайн без nonce — CSP заблокирует
- НЕ забывать `@csrf` в формах POST
- НЕ показывать `meta_description` пустой — fallback на excerpt модели или
  на default из Settings
