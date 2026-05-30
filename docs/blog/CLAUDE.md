# CLAUDE.md — Blog subsystem

> Engineering conventions for the triad.kz blog (Article + BlogCategory
> + their Filament resources, views, schema partials).
>
> **Сестринский документ:** [PLAN.md](./PLAN.md) — стратегия,
> SEO-калибровка, web research, change log, phase roadmap.
>
> **Корневой контекст:** [/CLAUDE.md](../../CLAUDE.md) — project-wide
> правила (deploy, security, code style, commit conventions).
>
> Этот файл — engineering layer. Сюда смотрят AI/dev при работе
> непосредственно с кодом блога. Антипаттерны и rationale здесь
> приоритетны над общим описанием — общее в PLAN.md.

---

## 0. Скоуп владения

Этот CLAUDE.md покрывает поведение в следующих файлах/путях:

| Path | Что |
|---|---|
| `app/Models/Article.php` | Главная модель публикации |
| `app/Models/BlogCategory.php` | Модель рубрики |
| `app/Filament/Resources/Articles/**` | Админка статей |
| `app/Filament/Resources/BlogCategories/**` | Админка рубрик |
| `resources/views/blog/index.blade.php` | Главная блога |
| `resources/views/blog/article.blade.php` | Страница статьи |
| `resources/views/blog/category.blade.php` | Страница рубрики *(Phase 1 в работе)* |
| `resources/views/partials/schema/article.blade.php` | JSON-LD статьи |
| `resources/views/partials/schema/organization.blade.php` | JSON-LD организации (singleton) |
| `resources/views/partials/schema/blog-category.blade.php` | JSON-LD CollectionPage *(Phase 1 todo)* |
| `resources/views/partials/schema/breadcrumb.blade.php` | JSON-LD BreadcrumbList *(Phase 1 todo)* |
| `routes/web.php` *(блок `/blog/*`)* | Маршруты |
| `database/migrations/*_*blog*` | Миграции рубрик и расширения статей |
| `app/Http/Controllers/BlogController.php` | Контроллер блога |
| `app/Services/ContentToc.php` *(Phase 1 todo)* | Парсер TOC из H2/H3 |

Если правишь файл **не из списка** — этот документ может не применяться,
проверь corresponding CLAUDE.md соседнего модуля.

---

## 1. Канонические решения (decision log)

Каждое решение — со ссылкой на rationale. Не менять без understanding the
trade-off. Когда меняешь — обновляй и этот файл и PLAN.md §22 change log.

### 1.1. Publisher-only attribution

**Решение:** `Article.author == Article.publisher == Organization`
(@id `https://triad.kz/#organization`).

**Почему:** ЖБИ не YMYL ниша. Person entity влияет на ранжирование 1-3%,
AI-цитирование +10-15%. Operational cost (реальный инженер, реальная био,
поддержка sameAs) высокий. Fake byline = Google Spam Policies → manual action.

**Когда пересмотреть:** появился верифицированный эксперт-инженер у
заказчика. Тогда Author entity возвращается в Phase 2 как опциональная
feature, не как обязательная фича.

**Файл impact:** `app/Models/Article.php` (нет `author_id`, нет `author()`
relation), `partials/schema/article.blade.php` (author = Organization @id).

### 1.2. `updated_content_at` — read-only + action-only

**Решение:** колонка `updated_content_at` ставится **только** через
`EditArticle::markUpdatedAction()`. В Filament-форме поле `disabled +
dehydrated(false)`. Никаких других mutators.

**Почему:** Google Helpful Content Update (2023+) карает за fake-freshness
паттерны — частое обновление timestamp без существенных правок контента
снижает rank. Если поле editable в форме, редактор поставит «вчера» и
смысл механизма теряется.

**API:**
```php
// Use:
$action = $editArticle->markUpdatedAction();   // через Filament UI

// DON'T do:
$article->updated_content_at = now();          // обходит защиту
$article->update(['updated_content_at' => $x]); // обходит защиту
```

Fallback на `published_at` в `effectiveModifiedAt()` — используется для
`schema.org dateModified`, всегда возвращает валидную дату.

### 1.3. Composite index `[blog_category_id, published_at]`

**Решение:** `[blog_category_id, published_at]` — equality-first,
не `[published_at, blog_category_id]`.

**Почему:** hot listing query это:
```sql
SELECT * FROM articles
WHERE blog_category_id = ? AND published_at <= NOW()
ORDER BY published_at DESC
```

MySQL/PostgreSQL планировщики предпочитают prefix-match на equality
column, затем range scan. Reverse order не позволил бы prefix-matchить
по category.

**Migration:** `2026_05_30_020002_add_seo_fields_to_articles_table.php`,
индекс `articles_cat_pub_idx`.

### 1.4. Reading-stats regex через `\p{P}`

**Решение:** `preg_split('/[\s\p{P}]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY)`,
не `preg_match_all('/\b\w+\b/u', $plain)`.

**Почему:** `\b` с флагом `/u` в PCRE2 (PHP 7.3+) **формально работает** для
кириллицы ([PHP Manual: regexp Unicode](https://www.php.net/manual/en/regexp.reference.unicode.php)).
Выбор `\p{P}` — стилистический: explicit «whitespace + punctuation»
вместо implicit word boundary; устойчивее к edge cases (составные/
дефисированные слова) и независимо от UCD-таблицы конкретной PCRE-
сборки. Это не «фикс бага», это **clarity choice**.

**Файл:** `app/Models/Article.php::recomputeReadingStats()`.

### 1.5. Spatie cover конверсии 3 ratios + min-width 1200

**Решение:** Article cover имеет 7 конверсий, включая `schema_1_1`,
`schema_4_3`, `schema_16_9` (все 1200px+ по широкой стороне). В форме —
валидация `dimensions:min_width=1200` на upload.

**Почему:** Google Article schema рекомендует image[] с 3 aspect ratios.
Без min-width защиты Spatie проапскейлит мелкое исходное фото до 1200,
получим blurry thumbnails в SERP. Search Console предупреждает об этом.

**Файлы:** `app/Models/Article.php::registerMediaConversions()`,
`app/Filament/Resources/Articles/Schemas/ArticleForm.php` (validation rule).

### 1.6. `BlogCategory` ≠ `Category`

**Решение:** отдельная модель + таблица `blog_categories` для рубрик блога;
не reuse каталоговой `Category`.

**Почему:** Slug namespace и SEO-funnel блога и каталога должны быть
независимыми. Шаринг таблицы сложил бы несвязанные concerns:
- Каталог индексируется в Y.Маркете / Google Shopping; блог — в SERP
- Блог-рубрика и каталог-категория могут иметь одинаковое имя но разные
  slug-конвенции
- Изменения схемы (FK, fields) одной модели не должны затрагивать другую

### 1.7. Meilisearch с дня 1 (не MySQL FULLTEXT)

**Решение:** Phase 3 `/blog/search` через Meilisearch с русскими
analyzers, не через MySQL FULLTEXT.

**Почему:** MySQL/MariaDB FULLTEXT-парсеры **не поддерживают русскую
морфологию**. «бетон» не найдёт «бетонный/бетонная». False economy
«поставим FULLTEXT пока статей мало, перейдём на Meilisearch потом» —
переписывать дороже чем сделать сразу.

### 1.8. Indexing protocol matrix

| Search engine | Protocol | Когда |
|---|---|---|
| Yandex | IndexNow | Post-save observer (publish/update/delete) |
| Bing | IndexNow (тот же endpoint) | Тот же observer |
| Google | **ничего программно** | Search Console manual submit + natural crawl |

**Не пинговать Google sitemap** — endpoint удалён июнь 2023 (`ping?sitemap=`
возвращает 404).

---

## 2. Антипаттерны (НЕ делать)

Каждый антипаттерн = реальная ошибка которую можно допустить. Read this
перед PR review.

| ❌ Don't | ✅ Use instead | Why |
|---|---|---|
| Auto-touch `updated_content_at` на save | Action-only через `markUpdatedAction` | Google карает за fake freshness |
| Fake author byline | Publisher-only (Organization как author) | Spam Policies, manual action risk |
| `FAQPage` schema на каждом pillar | Только при реальных Q&A из Wordstat/GSC | Keyword-stuffing flag |
| `preg_match_all('/\b\w+\b/u', ...)` для word count | `preg_split('/[\s\p{P}]+/u', ...)` | Clarity + устойчивее к составным словам (НЕ потому что `\b` сломан) |
| MySQL FULLTEXT для русского блога | Meilisearch с русскими analyzers | Нет морфологии |
| `view_count++` в middleware на каждый запрос | Y.Metрика Reports API в cache | БД-нагрузка + race conditions |
| Ping Google sitemap endpoint | Search Console submit + IndexNow для Yandex | Endpoint удалён июнь 2023 ([Google Search Central](https://developers.google.com/search/blog/2023/06/sitemaps-lastmod-ping)) |
| `<meta name="keywords">` для Yandex | Только `<meta name="description">` | Yandex игнорит keywords с 2014 |
| HowTo schema ожидая SERP rich results | HowTo только для AI extraction (Phase 2) | Google убрал HowTo SERP август 2023 ([Search Central](https://developers.google.com/search/blog/2023/08/howto-faq-changes)) |
| `SpeakableSpecification` schema для B2B-блога | Definitions-on-top + TL;DR block | Schema валидна и BETA, но scope **только news** ([Google Speakable docs](https://developers.google.com/search/docs/appearance/structured-data/speakable)). Не deprecated, но irrelevant для нашего use case |
| Фейковые цифры Wordstat / market share | Mark как «TBD» + источник | Documentation truth > convenience |
| Шарить slug namespace `Category` и `BlogCategory` | Отдельные модели и таблицы | Coupling непохожих concerns |
| Index `[published_at, blog_category_id]` | `[blog_category_id, published_at]` | Equality-first для hot query |
| Полагаться на Larastan cast type-narrowing в union return | Явные `/** @var Carbon\|null */` аннотации | Stub limitation в larastan |
| Cover image upload без min-width validation | `dimensions:min_width=1200` rule | Blurry SERP thumbnails |
| Inline-create BlogCategory из ArticleForm | Создавать через BlogCategoryResource | Огрызок без description/cover/SEO |
| `pillar_id` без `is_pillar` | `is_pillar` bool + `pillar_id` self-ref | Двусмысленность NULL pillar_id |
| Tags pivot table | `article_product` + `article_gost` + `article_category` (Phase 2) | Thin-content tag URL'ы каннибализируют |
| News sitemap для B2B-ЖБИ блога | Не делать пока 5+ news/нед нет | Пустой news-sitemap = шум |
| Counter inkrement через POST middleware с cookie debounce | Yandex.Metрика API + Redis cache | Debounce cookie обходится |
| `rel="prev"/"next"` для пагинации | Canonical на себя на каждой `/blog/page/N` | Google игнорит rel=prev/next с 2019 |
| `Organization.hasMap` для Y.Бизнес связки | Webmaster region binding + Y.Бизнес claim domain | hasMap не управляет Y.Бизнес |
| Inline JSON-LD в blade view | Partial `partials/schema/*.blade.php` | Переиспользование + audit |

---

## 3. SEO-инварианты (не нарушать)

Вещи которые **должны быть** на каждой странице блога. Эти не «рекомендация»
— они foundational.

### 3.1. Canonical всегда self

`<link rel="canonical" href="{url()->current()}">` — если admin не задал
явный `canonical_url` в SEO-секции.

Реализация: `partials/head.blade.php`, использует `$model->canonical_url ?:
url()->current()`.

### 3.2. `noindex` flag работает per-row

`Article.noindex` и `BlogCategory.noindex` — boolean колонки. При true
вставляется `<meta name="robots" content="noindex, nofollow">` на странице.

Не оверрайдить из partial; honor flag на head.blade.php уровне.

### 3.3. `@id` linkage в schema graph

Каждая Organization ссылка — через `@id`, не embedded:
```json
"publisher": { "@id": "https://triad.kz/#organization" }
```
Не:
```json
"publisher": { "@type": "Organization", "name": "...", "logo": "..." }
```

`@id` linkage позволяет Google склеить граф сущностей.

### 3.4. Breadcrumb на статье и категории

Visual `<x-breadcrumb>` компонент + `BreadcrumbList` JSON-LD на той же странице.

Реализация breadcrumb partial берёт items array, эмитит JSON-LD —
переюзаемо для статьи, категории, /blog hub.

### 3.5. IndexNow ping на publish/update/delete *(Phase 2)*

Через Article/BlogCategory observers. НЕ через ручной trigger в
контроллере — иначе пропустим bulk-операции из tinker / Filament bulk
actions.

> Это **будущий инвариант** (Phase 2). Сейчас в инвариантной секции
> для напоминания при имплементации.

---

## 4. URL conventions

### 4.1. Slug правила

```
Article.slug         — globally unique, kebab-case latin
BlogCategory.slug    — namespace blog_categories, kebab-case latin
URL: /blog/{slug}    — НЕ /blog/{category}/{slug}
```

Уникальность Article.slug глобально — статья может перейти в другую
рубрику без ломки URL.

### 4.2. Slug auto-redirect

`HasSlugRedirect` trait на обоих моделях. При смене slug:
- Old URL → new URL пишется в `redirects` таблицу как 301
- Middleware `MissingPageRedirector` ловит 404 и редиректит
- Уже работает для Product / Category / Page / Article / BlogCategory

### 4.3. Что НЕ должно быть в URL

- ✗ `/blog/{category}/{slug}` — slug привязан к категории, ломается при move
- ✗ `/blog/tag/{tag}` — тегов нет, заменено M2M-связями
- ✗ `/blog/author/{slug}` — авторов нет (publisher-only)
- ✗ `/blog/{year}/{month}/{slug}` — wordpress-стиль, ломается при backdating

---

## 5. Media conventions

### 5.1. Article cover collection

| Conversion | Размер | Назначение |
|---|---|---|
| `thumb` | 300×300 | Listing thumb |
| `card` | 600×400 | Card grid |
| `og` | 1200×630 | Open Graph + Twitter |
| `hero` | 1600 wide | Hero на article page |
| `schema_1_1` | 1200×1200 | schema.org image[] (1:1) |
| `schema_4_3` | 1200×900 | schema.org image[] (4:3) |
| `schema_16_9` | 1200×675 | schema.org image[] (16:9) |

Все конверсии `nonOptimized()` — Plesk shared блокирует `proc_open`,
Spatie optimizer'ы (jpegoptim, pngquant) не работают.

### 5.2. Form validation

```php
SpatieMediaLibraryFileUpload::make('cover')
    ->collection('cover')
    ->image()
    ->rules(['dimensions:min_width=1200'])
```

Без `min_width=1200` schema_*-конверсии upscale до 1200 → blurry в SERP.

### 5.3. `imageAlt()` описывает картинку, не страницу

```php
// Article
return $this->title;   // illustrative, no city/brand tail

// BlogCategory  
return 'Иллюстрация рубрики «'.$this->name.'» — блог ТРИ АД';
// НЕ: $this->name.' — каталог статей в Алматы' (это про страницу)
```

WCAG: alt описывает image content, не page metadata.

---

## 6. Filament conventions

### 6.1. ArticleResource form sections

| Section | Поля | State |
|---|---|---|
| Статья | blog_category_id (Select, required), title, subtitle, slug, published_at, excerpt, cover, content | Editable |
| Авто-статистика и обновления | word_count, reading_minutes, updated_content_at | `disabled + dehydrated(false)` |
| SEO и социальные сети | meta_title, meta_description, og_image_override, canonical_url, noindex | `collapsed` |

### 6.2. EditArticle header actions

Порядок:
1. `markContentUpdated` — главный action для freshness signal
2. `InternalLinkPickerAction` — встроенная перелинковка
3. `DeleteAction` / `ForceDeleteAction` / `RestoreAction`

### 6.3. BlogCategoryResource form

Section «Рубрика» содержит: name, slug, description (RichEditor),
order, published, listed, cover.

SeoSection collapsed внизу — meta_title/desc/canonical/noindex для рубрики.

### 6.4. Reorderable table

BlogCategoriesTable использует `->reorderable('order')` — drag-handle в
UI меняет `order` колонку напрямую.

### 6.5. BlogCategory inline-create из ArticleForm — запрещён

BlogCategory inline-create **запрещён**. Эта рубрика требует description,
cover, SEO-поля и валидацию длины. Inline-форма из 3 полей создала бы
огрызок, который потом пришлось бы дозаполнять руками.

Редактор создаёт BlogCategory через её собственный Resource.

---

## 7. Schema.org graph — file map

Текущее состояние filesystem (`resources/views/partials/schema/`):

| Partial | What | Когда подключать | Статус |
|---|---|---|---|
| `organization.blade.php` | Organization singleton (`@id` graph root) | На каждой странице (через head) | ✅ есть |
| `local-business.blade.php` | LocalBusiness (адрес + openingHours для local pack) | На главной + контакты | ✅ есть |
| `article.blade.php` | BlogPosting (per article) | `blog/article.blade.php` | ✅ есть (требует расширения — см. Phase 1 P0 todo) |
| `breadcrumb.blade.php` | BreadcrumbList | Везде с breadcrumb-навигацией | ✅ есть |
| `product.blade.php` | Product (для каталога) | `catalog/product.blade.php` | ✅ есть |
| `blog-category.blade.php` | CollectionPage (per blog rubric) | `blog/category.blade.php` | ⏳ Phase 1 P0 todo |

**Что НЕ создаём:**
- ~~`website.blade.php`~~ — WebSite + SearchAction делаем inline в layout
  на главной + `/blog` (не нужен отдельный partial — это singleton)

Каждый partial — отдельный `<script type="application/ld+json">` блок.
Google склеивает graph через `@id` references.

---

## 8. Reading stats и `updated_content_at` — runtime semantics

### 8.1. `Article::recomputeReadingStats()` — single source of truth

Триггер: `static::saving()` в `Article::booted()` при
`isDirty('content') || word_count === null`.

**Запускается при:**
- save'е в Filament **где `content` реально изменился** (isDirty)
- save'е новой статьи (word_count === null на первом save'е)
- seed / импорте новых статей (word_count === null)

**НЕ запускается при:**
- Save'е где меняется только title / meta_title / etc. (без `content`)
  — reading_minutes остаётся прежним, что корректно
- `saveQuietly()` — событие saving не триггерится. Поэтому
  `markUpdatedAction()` использует `forceFill + saveQuietly` чтобы не
  пересчитывать stats повторно (когда меняется только
  `updated_content_at`)

**Backfill для legacy статей** (с word_count = null после миграции):
admin commit любую правку в форме → save → пересчёт. Или artisan
command `articles:backfill-reading-stats` (Phase 1 todo).

### 8.2. `Article::effectiveModifiedAt()` — fallback на published

```php
return $this->updated_content_at ?? $this->published_at;
// (с инлайн @var аннотацией из-за larastan stub limitation)
```

Используется в:
- `partials/schema/article.blade.php::dateModified`
- Sitemap entry `lastmod`
- Open Graph `article:modified_time`

### 8.3. `Article::relatedInBlogCategory($limit, $exclude)` — Related block

Возвращает Eloquent Collection статей той же `blog_category_id`,
исключая self и `$exclude`. Используется:
- «Также в категории» блок на article page (Phase 1)
- В Phase 2 `$exclude` будет содержать id'шники pillar/cluster блока для
  deduplication

---

## 9. Что добавлять в Phase 2 P1 (cheatsheet)

См. полный roadmap в [PLAN.md §20](./PLAN.md#20-phase-plan).

### Если попросят добавить «теги»
**НЕТ.** Используем M2M-связи (article_product, article_gost,
article_category). См. [PLAN.md §3.4](./PLAN.md#34-теги--не-нужны).

### Если попросят FAQ-блок
Проверь: есть ли **реальные 4-8 вопросов** из Wordstat/GSC? Если нет —
**не добавляем** schema-разметку. Добавляем только FAQ render-блок без
JSON-LD. См. [PLAN.md §7.5](./PLAN.md#75-faqpage-phase-2-опционально).

### Если попросят HowTo
Только для AI extraction (Phase 2). НЕ ожидать SERP-эффекта.

### Если попросят Author entity
Перечитать [PLAN.md §3.1](./PLAN.md#31-author-attribution--publisher-only). Если у заказчика появился
реальный инженер с био — добавлять с условиями (no fake bios, no
stock photos, real LinkedIn). Иначе — НЕТ.

### Если попросят «search через MySQL FULLTEXT»
НЕТ. Сразу Meilisearch.

---

## 10. Debug / smoke / lint

### 10.1. Миграции

```
migrate
migrate:status
migrate:rollback --step=2
```

### 10.2. Lint

```bash
vendor/bin/pint app/Models/Article.php app/Models/BlogCategory.php
vendor/bin/pint app/Filament/Resources/Articles app/Filament/Resources/BlogCategories
vendor/bin/phpstan analyse
```

### 10.3. Schema validation

- Открыть https://search.google.com/test/rich-results
- Paste article URL → проверить:
  - Article / BlogPosting detected
  - Breadcrumb detected
  - Organization detected
  - 3 image ratios все loadable
  - `@id` references resolve

### 10.4. Yandex Webmaster

- https://webmaster.yandex.ru/site/<domain>/
  - Регион → Алматы, KZ
  - IndexNow status (после Phase 2)
  - Sitemap registered

### 10.5. Smoke browser

После rendering правок:
- Breadcrumb visible + clickable
- Reading time shown
- TOC рендерится (после Phase 1 todo)
- Y.Metрика fires `article_view` (после Phase 3 events)
- Cover 1200+ load, responsive

### 10.6. Tests (когда добавим)

```bash
vendor/bin/pest --filter=BlogTest
vendor/bin/pest --filter=ArticleObserverTest
```

---

## 11. Content sanitization — security gap (Phase 2 fix)

**Текущее состояние (2026-05-30):**
- `mews/purifier` пакет **установлен** ([config/purifier.php](../../config/purifier.php) configured)
- `Article.content` пишется WYSIWYG (Filament RichEditor → TipTap)
- Рендерится в [blog/article.blade.php](../../resources/views/blog/article.blade.php)
  через `{!! $article->content !!}` (bypass Blade escape)
- **НО** ни setter, ни observer на Article **не вызывают** `clean()` —
  HTML попадает в БД и в HTML страницы сырым

**Риск:**
- Если админ-аккаунт скомпрометирован → admin может вставить
  `<script>` через TipTap (TipTap front-end **не санитизирует**, он
  WYSIWYG-редактор, не security tool)
- Если когда-нибудь добавим Phase 3 комментарии или user-submitted
  content — XSS немедленно

**Phase 2 fix:**
1. Добавить в Article setter:
   ```php
   public function setContentAttribute(?string $value): void
   {
       $this->attributes['content'] = $value === null
           ? null
           : clean($value, 'default');
   }
   ```
2. Backfill: `php artisan articles:resanitize-content`
3. Тест: попытаться сохранить `<script>alert(1)</script>` через Filament
   → должно превратиться в empty или escaped

**Sanitization on save, not on render** — иначе double-escape +
кэширование сложно ([Larasec на тему](https://stackshield.io/blog/laravel-xss-protection-guide)).

---

## 12. Preview черновика

Когда статья имеет `published_at = NULL` (черновик) или
`published_at > NOW()` (запланирована), её `/blog/{slug}` URL отдаёт
404 (по `scopePublished` в `BlogController::show`).

**Как админу проверить как будет выглядеть?**

Текущее: открыть в новой вкладке `/blog/{slug}?preview=1` — middleware
проверяет `Auth::user()?->canViewDraft($article)` (Phase 2 todo).

Альтернатива (Phase 2): Filament Action «Preview» в `EditArticle`
header → открывает страницу с signed URL (15-min TTL token).

---

## 13. OG image fallback chain

Цепочка fallback'а для `og:image` на странице статьи (head.blade.php):

```
1. $article->meta_og_image_override  // ручной override в SEO-секции
   ↓ если пусто
2. $article->getFirstMediaUrl('cover', 'og')  // article cover конверсия 'og'
   ↓ если пусто
3. $settings->getFirstMediaUrl('og_default')  // глобальный fallback в Settings
   ↓ если пусто
4. <без og:image>  // пропускаем тег (лучше чем пустой src)
```

Шаг 3 настроен в [partials/head.blade.php](../../resources/views/partials/head.blade.php).
Шаг 1 — Phase 2 todo (поле в SEO-секции).

---

## 14. SQLite (local dev) vs MySQL/MariaDB (prod) — нюансы

Project использует SQLite локально (`database/database.sqlite`), MySQL
в проде. Большинство кода работает идентично, но **знай различия:**

| Что | SQLite | MySQL/MariaDB |
|---|---|---|
| `\p{P}` regex в PHP | работает (PCRE-уровень, не БД) | работает |
| JSON columns | `TEXT` под капотом, без JSON-индексов | native JSON + JSON_EXTRACT |
| WHERE по JSON-полю | LIKE-обход или Eloquent JSON-syntax | где доступно |
| FULLTEXT-индекс | нет (используем Meilisearch) | есть, но без рус. морфологии |
| Composite index `[a,b]` | поддерживается | поддерживается |
| `ON DELETE RESTRICT` | поддерживается | поддерживается |
| Soft delete column | `deleted_at` нормально | нормально |
| Timezone-aware timestamps | UTC-naive | TIMESTAMP w/ TZ awareness |

**Что НЕ ловится локально:**
- Performance regressions из-за неиндексированных JOIN'ов (SQLite быстрее
  fake-эффект на 10 строках)
- MySQL-specific deadlocks при concurrent writes
- FULLTEXT-сценарии (мы их избегаем — см. §1.7)

**Тестировать против MySQL** перед production-deploy для критичных
миграций (Phase 2: `article_product` M2M, `faq` JSON queries).

---

## 15. Связанные документы

- [PLAN.md](./PLAN.md) — стратегия + SEO калибровка + sources
- [/CLAUDE.md](../../CLAUDE.md) — project-wide
- [/database/migrations/CLAUDE.md](../../database/migrations/CLAUDE.md) — migration conventions
- [/app/Filament/CLAUDE.md](../../app/Filament/CLAUDE.md) — Filament conventions
- [/resources/views/CLAUDE.md](../../resources/views/CLAUDE.md) — Blade conventions

---

## 16. Change log

| Date | Change |
|---|---|
| 2026-05-30 | Initial. Created post-critique, после Phase 1 P0 backend landing. |
| 2026-05-30 (later) | Self-critique pass 2: fact-checked claims via web research. Corrected: `\b` regex (overclaim → clarity choice), Yandex Metrika Reports API (was correct after all), SpeakableSpec (not deprecated, just BETA/news-scope), KZ market share (Google ~74% not 60%), schema partial map (added local-business, removed fictional website partial). Added §11-§14: content sanitization gap, preview drafts, OG fallback chain, SQLite vs MySQL nuances. |
