# triad.kz blog — SEO architecture & plan

> **Версия:** 2.0 (2026-05-30, post-critique rewrite)
> **Предыдущая версия:** v1 при `docs/blog-seo-plan-2026.md`, commit
> [d61b3b82](../../).
> **Sister doc:** [CLAUDE.md](./CLAUDE.md) — engineering conventions
> (антипаттерны, paths, decision rationale).
>
> v2 — finalized state, без strike-through и warning-блоков. История
> исправлений зафиксирована в §22 Change log.

---

## 0. Метаданные плана

| Поле | Значение |
|---|---|
| Subject | SEO-оптимизированный блог B2B-ЖБИ каталога |
| Target traffic | Информационные queries → внутренняя перелинковка в каталог |
| Primary geo | Алматы, Казахстан |
| Primary search engines | Yandex (~60% KZ B2B), Google (~40%) |
| Emerging channel | AI search (Perplexity, ChatGPT, AI Overviews) |
| Author entity | **Не делаем** (publisher-only — см. §3.1) |
| Search backend | Meilisearch (с дня 1, не MySQL FULLTEXT) |
| Indexing protocol | IndexNow для Yandex/Bing, natural crawl + GSC для Google |

---

## 1. Стратегический контекст

Каталог отвечает на коммерческие запросы («купить ФБС12.4.6», «цена
бетонного кольца КС10.9»). Этого мало — **~80% поискового трафика** ниши
ЖБИ приходит на информационные запросы: «как выбрать ФБС блоки для
подвала», «расшифровка маркировки бетонных колец», «расчёт количества
колец для септика», «отличия ГОСТ 8020-90 и серии 3.900.1-14», «марки
бетона для фундамента». Блог ловит эти запросы и через внутреннюю
перелинковку гонит трафик в каталог.

**Цели**

1. **Информационный трафик** — pillars + guides под Wordstat-кластеры
2. **AI-цитирование** — попадание в Perplexity / ChatGPT / AI Overviews
   ответы как авторитетный источник по ЖБИ-теме
3. **Topical authority** — Yandex и Google склеивают домен с экспертной
   нишей, что бустит ранжирование и каталог-страниц

---

## 2. SEO-приоритеты (калиброванно по реальному влиянию)

Калибровка основана на 2026 research + критическом анализе. **Доли —
порядок величины, не точные числа.**

| Фактор | Доля влияния | Что делаем |
|---|---|---|
| Качество и глубина контента | ~50% | Длина по purpose, не по числу. Реальные ГОСТы, реальные размеры, реальные фото. |
| Off-page (backlinks, brand mentions) | ~25% | Out of scope этого плана — PR + партнёры. |
| Тех. SEO (CWV, sitemap, structure, indexing) | ~15% | Phase 1 P0 + 2 P1 — фундамент. |
| Internal linking (topic clusters) | ~10% | Pillar+cluster, M2M товары/ГОСТы. |
| Schema.org структурированные данные | ~5% | Article + Organization + Breadcrumb. Не больше. |

**Author entity намеренно не делаем** — обсуждалось, не оправдывает
operational cost для B2B-ниши (не YMYL). Полное обоснование — §3.1.

---

## 3. Архитектура контента

### 3.1. Author attribution — publisher-only

**Решение:** Organization (юр.лицо «ТРИ АД Construction») в JSON-LD одновременно
как `Article.author` и `Article.publisher`. Никакого Person entity, никаких
`/blog/author/{slug}` страниц.

**Обоснование:**

| Аргумент | Деталь |
|---|---|
| Не YMYL ниша | ЖБИ ≠ медицина/финансы/право. E-E-A-T-фильтр Google не активируется сильно. |
| Слабый ранжирующий эффект | Person entity: 1-3% буст ранжирования (по данным независимых SEO-исследований 2024-2026) |
| AI citation buster | Person + sameAs даёт +10-15% к шансу цитирования у Perplexity/ChatGPT — заметно, но не центральный канал |
| Operational cost высокий | Нужен реальный инженер, реальная био, реальное фото, поддержка sameAs ссылок, актуальный профиль |
| Spam-риск выше benefit'а | Фейковый byline → Google Spam Policies → manual action. Yandex АГС за то же |

**Когда возвращать Author:** появился верифицированный эксперт-инженер у
заказчика, готов под именем публиковаться, реальные регалии. Phase 2+,
отдельный PR.

### 3.2. Типы статей

| `article_type` | Назначение | Длина (range) | Доп. schema |
|---|---|---|---|
| `pillar` | Обзор «всё про X» | 2500-4000 слов | FAQPage *(если реально 4-8 Q&A)* |
| `guide` | How-to («как выбрать», «как считать») | 1500-2500 слов | HowTo *(только для AI extraction)* |
| `comparison` | «X vs Y» | 1200-2000 слов | — |
| `news` | Новости компании / ГОСТов | 400-800 слов | — |
| `case` | Кейсы объектов | 800-1500 слов | ImageGallery |

**Заметка о длине:** числа — range для редактора, не KPI. Google неоднократно:
«у нас нет word-count factor». Длина — функция от темы.

### 3.3. Рубрики — `BlogCategory`

**~7 топ-уровневых, плоский список** (без parent_id). Каждая = тематический
хаб = pillar topic.

| Категория | Pillar | Wordstat |
|---|---|---|
| Бетонные кольца | «Бетонные кольца КС: полный справочник» | 12k/мес |
| ФБС блоки | «Фундаментные блоки ФБС: маркировка, ГОСТ, монтаж» | 8k/мес |
| Плиты перекрытия | «Плиты перекрытия колодцев: ПП, серия 3.900.1-14» | 3k/мес |
| Теплотрассы | «Лотки и опорные подушки для теплотрасс» | — |
| ГОСТы и серии | «Справочник ЖБИ ГОСТов» | 2k/мес |
| Расчёты и сметы | «Калькуляторы и формулы для подсчёта ЖБИ» | — |
| Монтаж и эксплуатация | … | — |

URL: `/blog/category/{slug}`. **Не несём parent_id** — иерархии рубрик
блога создают канниализацию keyword'ов parent/child.

**`BlogCategory ≠ Category`** (catalog rubric) — отдельная модель и
отдельная таблица. Шаринг slug-namespace и SEO-funnel'а с каталогом
сложил бы несвязанные concerns.

### 3.4. Теги — НЕ нужны

Заменяем M2M-связями с реальными сущностями (Phase 2):

- `article_products` — статья → товары каталога
- `article_gosts` — статья → ГОСТ/серия (модель Gost уже есть)
- `article_categories` — статья → продуктовые категории

Это даёт двунаправленную перелинковку без размножения thin-content
tag-URL.

---

## 4. Data model

### 4.1. `BlogCategory` (Phase 1 P0 — landed)

| Колонка | Тип | Назначение |
|---|---|---|
| `id` | bigint | PK |
| `name` | varchar(255) | Название рубрики |
| `slug` | varchar(80) unique | URL-segment |
| `description` | longText nullable | WYSIWYG pillar-style intro 300-500 слов |
| `order` | unsigned int default 0 | Sort в `/blog` |
| `published` | bool default true | Полное скрытие |
| `listed` | bool default true | Скрыть из навигации блога (URL работает) |
| SEO fields | via `SeoFields::add()` | meta_title, meta_description, canonical_url, noindex, structured_data_override |
| `deleted_at` | timestamp | Soft delete |
| timestamps | | |

Spatie media collection `cover` (single file, conversions thumb/card/og/hero).

### 4.2. `Article` (Phase 1 P0 — landed + future fields)

**Phase 1 P0 (landed):**

| Колонка | Тип | Назначение |
|---|---|---|
| `id` | bigint | PK |
| `blog_category_id` | FK → blog_categories nullable, restrict | Рубрика (требуется в Filament) |
| `title` | varchar(255) | H1 |
| `subtitle` | varchar(300) nullable | Под H1 + alternativeHeadline |
| `slug` | varchar(255) unique | URL-segment |
| `excerpt` | varchar(500) nullable | Excerpt + meta_description fallback |
| `content` | longText | WYSIWYG |
| `word_count` | unsigned mediumint nullable | Авто (180 wpm для технического) |
| `reading_minutes` | unsigned smallint nullable | Авто |
| `published_at` | timestamp nullable | Null = черновик, future = scheduled |
| `updated_content_at` | timestamp nullable | Manual; используется как schema.org dateModified |
| SEO fields | via `SeoFields::add()` | |
| `deleted_at` | timestamp | |
| timestamps | | |

**Индекс:** `[blog_category_id, published_at]` composite (equality-first).

**Phase 2 P1 (планируется):**

| Колонка | Тип | Назначение |
|---|---|---|
| `article_type` | varchar(20) cast enum | pillar/guide/comparison/news/case |
| `is_pillar` | bool default false | Disambig: «эта без pillar» vs «сама pillar» |
| `pillar_id` | FK self-ref nullable, set null | Cluster → pillar |
| `featured` | bool default false | На главную /blog |
| `pinned_until` | timestamp nullable | Sticky в категории на срок |
| `toc_enabled` | bool default true | Отключение TOC |
| `faq` | JSON nullable | `[{question, answer}, ...]` |

**Pivot tables Phase 2:**
- `article_product`
- `article_gost`
- `article_category` *(catalog category)*

**Phase 3 P2 (если понадобится):**

| Колонка | Тип | Назначение |
|---|---|---|
| `how_to_steps` | JSON | Для AI extraction только |
| `external_sources` | JSON | Список «использованных источников» |
| `enable_comments` | bool | Когда модератор появится |

### 4.3. Что НЕ добавляем в Article

- **`author_id`** — publisher-only режим (§3.1)
- **`view_count`** — используем Yandex.Metрика Reports API (§16)
- **`redirect_to_id`** — существующая `redirects` таблица + middleware решает
- **Tags pivot** — заменено на entity M2M (§3.4)

---

## 5. URL-структура

```
/blog                              — index, paginated (12/page)
/blog/{slug}                       — Article detail (slug ГЛОБАЛЬНО уникальный)
/blog/category/{cat-slug}          — BlogCategory rubric
/blog/page/2                       — Pagination (canonical на себя, не на /blog)
/blog/search?q=...                 — Meilisearch (noindex, follow)
/blog/feed.rss                     — RSS 2.0 (Phase 3)
/blog/feed.atom                    — Atom (Phase 3)
```

**Slug-конвенция:** kebab-case латиница (Spatie Sluggable + транслитерация).

**Slug глобально уникален** для Article — не вложен в категорию.
Обоснование: статья может перейти в другую рубрику без ломки URL.

**Slug auto-redirect** — `HasSlugRedirect` trait уже подключён к Article и
BlogCategory. При смене slug автоматически пишется row в `redirects`
таблицу (existing infra).

---

## 6. Render features

### 6.1. Страница статьи `/blog/{slug}`

Сверху вниз:

1. **Breadcrumb** — `Главная → Блог → {Категория} → {Title}` + BreadcrumbList JSON-LD
2. **Article header:**
   - Pill категории (ссылка на `/blog/category/{slug}`)
   - H1 = `title`
   - Subtitle (если есть)
   - Метаданные: `published_at`, `updated_content_at` (только если ≠ published), `reading_minutes`, `word_count`
3. **TL;DR блок** *(Phase 3)* — если `[summary]` маркер в content
4. **Cover image** — `<figure>` с `<figcaption>`, srcset из Spatie conversions
5. **TOC** — авто из H2/H3 в content (sticky на desktop сбоку, dropdown на mobile)
6. **Content** — sanitized HTML с heading-anchors (id="{slug-of-heading}")
7. **FAQ блок** *(Phase 2)* — только если `faq` JSON непустой; `<details>` + FAQPage JSON-LD
8. **External sources** *(Phase 3)* — если `external_sources` непустое
9. **Related (P0):** «Также в категории» — 4 свежие статьи той же `blog_category`
10. **Related (Phase 2):** pillar/cluster блок + «С этим товаром покупают» (product cards)
11. **CTA:** «Связаться с инженером» / «Смотреть каталог в категории»
12. **Social share:** VK / Telegram / WhatsApp (статичные ссылки без JS-трекеров)

### 6.2. Reading stats — расчёт

При сохранении статьи (Article observer hook через `static::saving`):

```php
$plain = trim(strip_tags($this->content));
$parts = preg_split('/[\s\p{P}]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
$this->word_count = count($parts);
$this->reading_minutes = max(1, (int) ceil($word_count / 180));
```

**Почему `\p{P}` а не `\b`:** word boundary `\b` в PCRE Unicode-режиме
неконсистентно работает с кириллическими словами. `\p{P}` — Unicode
punctuation class — стабильно отделяет слова от препинания.

**Почему 180 wpm:** lower-bound для русского технического чтения. Generic
блог-калькуляторы используют 250 wpm (английский общий) — overstate для
ЖБИ контента.

### 6.3. `updated_content_at` — semantic + UX

Это **«значимое обновление контента»** signal для schema.org `dateModified`
— отдельно от Eloquent `updated_at` (тот меняется при любом save).

**Семантика:** редактор нажимает кнопку «Пометить обновлённой» **только**
после существенной правки контента, не при опечатке. Google Helpful
Content Update (2023+) явно карает за fake-freshness паттерны.

**Реализация:**
- Колонка `nullable timestamp`
- В Filament-форме поле `disabled + dehydrated(false)` — редактор не может
  ввести произвольную дату
- Header action `markContentUpdated` в `EditArticle` ставит `now()` через
  `forceFill + saveQuietly`

**`effectiveModifiedAt()`** на модели: возвращает `updated_content_at ??
published_at` — для schema.org даёт всегда валидную дату даже на свежих
статьях.

### 6.4. Страница `/blog` (главная блога)

- Hero: pillar категории «ГОСТы и серии» или последний `featured` pillar
- Featured (3-6 статей с `featured=true`)
- Pillars grid (по 1 на категорию)
- Последние статьи (paginated 12/page)
- Sidebar: «Популярные» *(Phase 3, из Y.Metрика API)*, список рубрик

### 6.5. Страница категории `/blog/category/{cat}`

- Breadcrumb + BreadcrumbList schema
- H1 = `name`
- `description` (WYSIWYG) — pillar-style intro 300-500 слов
- Cover image (hero size)
- Pinned/sticky первыми *(Phase 2)*
- Featured затем *(Phase 2)*
- Остальные по `published_at DESC`
- Pagination 12/page
- CollectionPage JSON-LD

---

## 7. Schema.org graph

### 7.1. Граф через `@id` linkage

| Entity | @id |
|---|---|
| Organization (singleton) | `https://triad.kz/#organization` |
| WebSite (для главной + SearchAction) | `https://triad.kz/#website` |
| BlogPosting (per article) | `https://triad.kz/blog/{slug}#article` |
| CollectionPage (per category) | `https://triad.kz/blog/category/{slug}#collection` |
| WebPage (на каждой странице) | URL страницы + `#webpage` |

Все ссылки внутри graph'а — `@id` references, не embedded дубли.

### 7.2. BlogPosting (per Article)

```json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "@id": "https://triad.kz/blog/{slug}#article",
  "headline": "{title}",
  "alternativeHeadline": "{subtitle}",
  "description": "{meta_description || excerpt}",
  "image": [
    "{cover schema_1_1 url}",
    "{cover schema_4_3 url}",
    "{cover schema_16_9 url}"
  ],
  "datePublished": "{published_at ISO8601}",
  "dateModified": "{effectiveModifiedAt() ISO8601}",
  "author":    { "@id": "https://triad.kz/#organization" },
  "publisher": { "@id": "https://triad.kz/#organization" },
  "mainEntityOfPage": { "@type": "WebPage", "@id": "{url}#webpage" },
  "wordCount": {word_count},
  "articleSection": "{blogCategory.name}",
  "about": [
    { "@type": "Product", "@id": "https://triad.kz/catalog/.../{product-slug}" },
    { "@type": "Thing", "name": "ГОСТ 8020-90" }
  ],
  "isPartOf": {
    "@type": "BlogPosting",
    "@id": "https://triad.kz/blog/{pillar-slug}#article"
  }
}
```

**Заметки:**

- `author` == `publisher` == Organization — publisher-only режим. Google
  явно поддерживает.
- 3 image ratios — **рекомендация Google**, не требование. Минимум 1 image
  >1200px по широкой стороне. 3 ratios даём для разных раскладок (Top
  Stories, Discover, Search).
- `BlogPosting` vs `Article` — для SERP **одинаково**. Google
  developers.google.com/search Article docs: «они share identical
  required/recommended properties». Выбор маркирует семантику, не
  ранжирование.
- `about` и `isPartOf` — Phase 2 (когда M2M-связи и pillar/cluster будут)

### 7.3. CollectionPage (per BlogCategory)

```json
{
  "@type": "CollectionPage",
  "@id": "https://triad.kz/blog/category/{slug}#collection",
  "name": "{name}",
  "description": "{description first paragraph}",
  "isPartOf": { "@id": "https://triad.kz/#website" },
  "breadcrumb": { "@id": "{url}#breadcrumb" },
  "mainEntity": {
    "@type": "ItemList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "url": "{article 1 url}" }
    ]
  }
}
```

### 7.4. BreadcrumbList

Отдельный JSON-LD блок на странице статьи и категории. Реализация через
переюзаемый partial `partials/schema/breadcrumb.blade.php` который
принимает items collection и эмитит JSON-LD.

### 7.5. FAQPage (Phase 2, опционально)

**Условие подключения:** в `Article.faq` JSON-поле есть **реальные 4-8
вопросов** из Wordstat / Search Console. Не добавляем FAQ ради schema-
маркапа — Google после 2023 показывает FAQ rich results очень селективно,
а Yandex не показывает вообще. **Ценность для AI extraction.**

Контент вопросов **обязан быть на странице** (`<details>` блок), иначе
Google помечает как spam (cloaking).

### 7.6. HowTo (Phase 2, опционально)

**Только для AI extraction.** Google убрал HowTo rich results из SERP в
**августе 2023** (mobile + desktop). Schema валидна, но SERP-эффект 0.
Perplexity/ChatGPT кушают `HowToStep` массив напрямую.

Подключаем только если статья реально step-by-step (например «Как
монтировать колодец из колец»).

### 7.7. Organization (singleton, глобальный)

Рендерится **один раз** в head (через `partials/schema/organization.blade.php`):

```json
{
  "@type": "Organization",
  "@id": "https://triad.kz/#organization",
  "name": "ТРИ АД Construction",
  "logo": "{logo url}",
  "url": "https://triad.kz/",
  "address": { "@type": "PostalAddress" },
  "sameAs": ["https://t.me/...", "..."],
  "contactPoint": { }
}
```

---

## 8. Open Graph + Twitter

| Поле | Откуда |
|---|---|
| `og:title` | `meta_title \|\| title` |
| `og:description` | `meta_description \|\| excerpt` |
| `og:image` | cover 1200×630 (Spatie 'og') |
| `og:type` | `article` |
| `og:locale` | `ru_RU` |
| `og:site_name` | `site_name` из Settings |
| `article:published_time` | ISO `datePublished` |
| `article:modified_time` | ISO `effectiveModifiedAt()` |
| `article:section` | название рубрики |
| `article:tag` | gosts.label + product names (Phase 2) |
| `twitter:card` | `summary_large_image` |

---

## 9. Topic clusters internal linking (Phase 2)

### 9.1. Правила (auto-enforce)

1. **Каждый кластер ссылается на pillar** — авто-блок «Эта статья — часть
   pillar [X]» внизу статьи (когда `pillar_id != null`)
2. **Pillar ссылается на ВСЕ кластеры** — авто-блок в pillar'е «В этой
   теме» с перечислением всех статей где `pillar_id = $this->id`
3. **Anchor text** в pillar↔cluster ссылках — `title` целевой статьи;
   никаких generic «подробнее»
4. **Каждая статья → 2-5 товаров** через `article_product` M2M
5. **Каждая статья → 1-3 ГОСТа** через `article_gost` M2M
6. **Минимум 2 inbound внутренние ссылки** на каждую опубликованную статью.
   Filament `ArticleResource` показывает счётчик `inbound_count`

### 9.2. Anchor text validation в Filament

Форма проверяет:
- Cluster-статья содержит ссылку на pillar — иначе warning
- Содержит ≥2 ссылок на товары — иначе warning
- Pillar содержит ссылки на все свои кластеры — иначе warning

---

## 10. Yandex-специфика (KZ-критично)

### 10.1. IndexNow protocol

Yandex и Bing (но не Google) поддерживают IndexNow. При publish / update /
delete статьи или категории — POST на `api.indexnow.org/IndexNow` с URL'ом.
Yandex и Bing обходят URL за 15 минут (vs 2-14 дней в classic-режиме).

Реализация — Laravel пакет `ymigval/laravel-indexnow` или собственный
observer на Article/BlogCategory.

Key file (8-64 hex chars) хранится в `public/{key}.txt`.

### 10.2. Yandex.Webmaster

- **Региональность** — привязка домена к Алматы, KZ через
  Y.Webmaster → Регион. Подтверждается через Yandex.Бизнес-карточку
  (claim domain on the business profile).
- **Sitemap submission** — sitemap-link в robots.txt + ручной submit в
  Webmaster UI.

### 10.3. Yandex.Бизнес-интеграция

Y.Бизнес карточка связывается с сайтом через **верификацию домена в
Webmaster**, не через `Organization.hasMap` в schema.org. Y.Бизнес-
профиль бустит local pack ранжирование для запросов типа «купить ФБС в
Алматы».

### 10.4. Meta-теги — Yandex чувствителен к canonical

- `<meta name="description">` обязателен, ≤160 chars
- `<meta name="robots">` явно
- `<link rel="canonical">` обязателен — Yandex борется с дублями жёстче Google
- **НЕ используем** `<meta name="keywords">` — Yandex игнорирует с 2014

---

## 11. Google-специфика

- IndexNow **не поддерживает** (заявил публично)
- **Sitemap ping endpoint удалён в июне 2023** — `ping?sitemap=` возвращает 404.
  Не пингуем.
- Реальные каналы submission: **Search Console manual submit** + sitemap-link
  в robots.txt + natural crawl

---

## 12. AI search optimization (GEO / AEO)

### 12.1. Structural patterns под AI extraction

- **Definitions on top.** Каждый guide/pillar начинается с 1-2 параграфов
  с прямым определением: «ФБС — это [определение]. Применяются для [...].
  Регламентируются [ГОСТ]». LLM-движки вырезают это как готовый ответ.
- **TL;DR блок** *(Phase 3)* — `[summary]` маркер в content, отдельно
  индексируется
- **Цифры в таблицах**, не в прозе. AI кушает `<table>` целиком как
  factual block.
- **Named entities явно в первом параграфе** — название организации, точные
  ГОСТы. Entity-anchors для AI.
- **Дата свежести.** Perplexity взвешивает <90 дней выше. Регулярно
  актуализировать через `markContentUpdated` action **только при реальных
  правках**.

### 12.2. FAQ — long-tail формулировки

Формулируем как user пишет в Wordstat / Perplexity:
- «Что такое ФБС?» — слишком generic
- «Чем отличается ФБС12.5.6-Т от ФБС24.4.6-Т?» — long-tail, который реально спрашивают

### 12.3. External authoritative outbound

Ссылки на авторитетные источники (gosstandart.gov.kz, rosstandart.gov.ru,
nostroy.ru) с `rel="external nofollow noopener"`. Для E-E-A-T-демонстрации +
trust signal для пользователей.

**Не как «LLM trick»** — utility ссылок для AI-цитирования не доказана.
Делаем потому что это honest contextual sourcing, а не keyword-hack.

---

## 13. Sitemap + indexing

| URL | Priority | Changefreq | lastmod |
|---|---|---|---|
| `/blog` | 0.7 | weekly | newest `published_at` |
| `/blog/category/{slug}` | 0.7 | weekly | newest `published_at` в категории |
| `/blog/{slug}` | 0.6 | monthly | `updated_content_at ?? published_at` |

**IndexNow ping** на publish/update/delete Article и BlogCategory.

---

## 14. Search internal `/blog/search`

**Meilisearch с дня 1**, не MySQL FULLTEXT.

Обоснование: MySQL/MariaDB FULLTEXT-парсеры (ngram + дефолтные) **не
поддерживают русскую морфологию**. Запрос «бетон» не найдёт «бетонный/
бетонная/бетонные». На русском это критичный gap — пользователи привыкли
к Yandex-морфологии. Meilisearch имеет встроенные русские analyzers.

Индексация:
- `Article.title` (boost 5)
- `Article.subtitle` (boost 4)
- `Article.excerpt` (boost 3)
- `Article.content` (boost 1)
- `BlogCategory.name` (filter)

Страница `/blog/search?q=...`: `noindex, follow`. Highlight matched
terms в snippet.

---

## 15. Performance / Core Web Vitals

Yandex с 2024 жёстче чем Google карает медленные страницы. Цели:

| Метрика | Цель | Как достигать |
|---|---|---|
| LCP | ≤ 2.5s | Cover `<img fetchpriority="high">`, preconnect к Y.Metрика / GA, WebP + JPEG `<picture>` fallback |
| CLS | ≤ 0.1 | Все `<img>` с `width`/`height`, шрифты `font-display:swap` + preload |
| INP | ≤ 200ms | TipTap-rendered content без JS-heavy блоков, Alpine.js для интерактива |

**Image conversions:** Spatie + Glide + Intervention. WebP-конверсия в
prod env (Plesk проверить наличие libwebp).

---

## 16. Engagement & analytics

### 16.1. Метрика-события (Y.Metрика + GA4)

Триггерятся Alpine.js на странице статьи:

| Event | Когда |
|---|---|
| `article_view` | На pageload |
| `article_read_25pct/50/75/100` | Scroll depth |
| `article_toc_click` | Клик на TOC якорь |
| `article_faq_open` | Раскрытие `<details>` FAQ |
| `article_product_click` | Клик на товар из article_products |
| `article_share_click` | VK/TG/WA кнопка |
| `article_cta_click` | CTA-блок |

Metрика использует эти ивенты в re-ranking — это **прямой engagement
сигнал** у Yandex.

### 16.2. Webvisor + heatmap

Y.Metрика → Webvisor включить для articles. Heatmaps покажут где пользователи
бросают чтение, какие H2 непонятны.

### 16.3. View counter — НЕ в БД

`articles.view_count`-инкремент в middleware = БД-нагрузка + race
conditions + cookie-debounce обходится curl-ом.

**Правильно:** «популярные» брать из **Yandex.Metрика Reports API**
раз в N часов в cache (Redis). Y.Metрика уже:
- фильтрует ботов
- считает уникальных пользователей
- даёт breakdown по статьям

Cron `php artisan blog:refresh-popular` раз в 4 часа → Redis hash → blade
читает из cache.

---

## 17. Комментарии

**Не делаем на старте.** Не потому что SEO-эффект ноль — он положительный
(engagement-сигналы у Yandex значимы), а потому что операционная цена
модерации без оперативного админа перевесит выгоду.

Позже — Disqus / Hyvor Talk с lazy-load (только при появлении модератора).

---

## 18. Filament UX-conventions

### 18.1. Live SEO-чек-лист на форме статьи (Phase 2)

Помимо стандартных meta_title / meta_description — добавить live
indicators:

- **Word count + reading time** автоматом (Phase 1 P0 — landed)
- **Title length indicator** (50-60 зелёный, 61-70 жёлтый, >70 красный)
- **Description length indicator** (140-160 зелёный)
- **Inbound link count** — сколько статей ссылается на эту
- **Outbound product links count** — заполнено ли article_products
- **Has cover?** / **Has category?** — чек-лист
- **FAQ filled?** *(для pillar/guide)*
- **Image alts filled?** — проверка media.custom_properties.alt
- **Has TL;DR?** — поиск `[summary]` в content
- **Date staleness** — если `updated_content_at` > 6 мес, warning

### 18.2. updated_content_at — read-only + action-only

- Поле в форме disabled + dehydrated(false) — нет редактирования
- `markContentUpdated` action в шапке `EditArticle`
- Reset-action (Phase 2) — обнулить дату обновления

### 18.3. Inline-create BlogCategory из статьи — нет

BlogCategory требует description / cover / SEO. Inline-create огрызок
снижает качество. Заставляем редактора создавать через BlogCategoryResource.

### 18.4. Cover image — min-width 1200

Form validation: `dimensions:min_width=1200`. Иначе schema_*-конверсии
upscale до 1200 → blurry SERP thumbnails.

---

## 19. `/blog` как hub

- WebSite + Organization schema (один раз глобально, через `@id`)
- CollectionPage schema для `/blog`
- SearchAction schema:

```json
"potentialAction": {
  "@type": "SearchAction",
  "target": "https://triad.kz/blog/search?q={search_term_string}",
  "query-input": "required name=search_term_string"
}
```

---

## 20. Phase plan

### Phase 1 — P0 (foundation)

| Status | Item |
|---|---|
| ✅ | Plan doc (this file) |
| ✅ | Migration `create_blog_categories_table` |
| ✅ | Migration `add_seo_fields_to_articles_table` (blog_category_id, subtitle, reading_minutes, word_count, updated_content_at) + composite index `[blog_category_id, published_at]` |
| ✅ | Model `BlogCategory` (HasSlug, HasSeo, HasMedia, HasSlugRedirect) |
| ✅ | Model `Article` (relations, booted saving hook, recomputeReadingStats, effectiveModifiedAt, relatedInBlogCategory, 3 schema_* media conversions) |
| ✅ | Filament `BlogCategoryResource` |
| ✅ | Filament `ArticleResource` — blog_category Select, subtitle, cover min-width, read-only stats, markContentUpdated action |
| ⏳ | Route `/blog/category/{slug}` |
| ⏳ | BlogCategoryController (или extend BlogController) |
| ⏳ | View `blog/category.blade.php` (CollectionPage rendering + BreadcrumbList) |
| ⏳ | Update `blog/article.blade.php` — breadcrumb, reading time, updated date, related-in-category |
| ⏳ | Service `ContentToc` (parse + inject H2/H3 ids, returns toc array) |
| ⏳ | TOC nav block в article view (sticky on desktop) |
| ⏳ | Extend `partials/schema/article.blade.php` — Organization-as-author+publisher, @id graph, 3 image ratios, wordCount, articleSection |
| ⏳ | Create `partials/schema/blog-category.blade.php` (CollectionPage) |
| ⏳ | Create `partials/schema/breadcrumb.blade.php` (reusable BreadcrumbList from items array) |
| ⏳ | Smoke tests + commit + push |

### Phase 2 — P1 (~3-4 дня)

- Migration add `article_type` + `is_pillar` + `pillar_id` + `featured` + `pinned_until` + `toc_enabled` + `faq` JSON
- Pivot `article_product` (M2M) + Filament UI
- Pivot `article_gost` (M2M) + Filament UI
- Pivot `article_category` (M2M to catalog) + Filament UI
- FAQ JSON Repeater в ArticleResource
- FAQ render блок + FAQPage JSON-LD
- Pillar/cluster auto-блоки в views (cluster→pillar inline, pillar→all clusters list)
- IndexNow integration (post-save observer + key file)
- Sitemap: добавить articles + blog_categories
- Filament SEO live-feedback indicators (live counts, чек-листы)
- Featured/pinned ordering в views

### Phase 3 — P2/P3 (~2-3 дня)

- Migration add `how_to_steps` + `external_sources`
- HowTo JSON-LD + render (для AI extraction)
- external_sources render блок
- TL;DR `[summary]` parser
- RSS + Atom feeds
- Y.Metрика Reports API integration → Redis cache → popular sort
- Метрика scroll/TOC/FAQ/share events (Alpine.js)
- Meilisearch integration + `/blog/search` view
- Comments (если модератор появится) — Disqus/Hyvor lazy-load

---

## 21. Sources (web research, 2026)

- [Technical SEO Checklist 2026 | DebugBear](https://www.debugbear.com/blog/technical-seo-checklist)
- [Article Schema | Google Search Central](https://developers.google.com/search/docs/appearance/structured-data/article)
- [Blog Schema Markup Guide 2026 | Superblog](https://superblog.ai/blog/blog-schema-markup-guide/)
- [SEO Content Clusters 2026: Topic Authority Guide](https://www.digitalapplied.com/blog/seo-content-clusters-2026-topic-authority-guide)
- [Internal Linking Strategy: B2B SEO](https://kerkarmedia.com/internal-linking-strategy-b2b-seo/)
- [Yandex SEO Guide: Russian & CIS](https://www.arjankc.com.np/blog/yandex-seo-guide-optimization/)
- [Optimizing for Yandex: comprehensive guide](https://www.weglot.com/blog/yandex-seo)
- [IndexNow Documentation](https://www.indexnow.org/documentation)
- [Laravel IndexNow integration](https://banatube.medium.com/supercharge-your-laravel-seo-with-indexnow-real-time-search-engine-notifications-f30726773657)
- [FAQ Schema: When It Helps in 2026](https://xugar.com.au/blog/faq-schema/)
- [Тренды SEO для B2B 2026 | ADPASS](https://adpass.ru/trendy-seo-dlya-b2b-v-2026-godu-kak-uvelichit-organicheskij-trafik-sajta/)
- [SEO в Яндексе 2026 гайд](https://lpmotor.ru/articles/seo-yandex-2026-polnyj-gajd-2603)
- [GEO: Citations in ChatGPT/Perplexity 2026](https://www.aimagicx.com/blog/generative-engine-optimization-chatgpt-perplexity-2026)
- [Answer Engine Optimization Guide 2026 | Frase](https://www.frase.io/blog/what-is-answer-engine-optimization-the-complete-guide-to-getting-cited-by-ai)

---

## 22. Change log

### v2.0 (2026-05-30) — post-critique rewrite

**Удалено / выкатано:**

- **Author entity** (model + Filament + ProfilePage + `/blog/author` URL) — publisher-only режим. Реальное влияние 1-3%, не окупает operational cost для не-YMYL ниши.
- **`view_count` колонка в БД** — заменено на Yandex.Metрика Reports API integration. Избавляет от race conditions и БД-нагрузки.
- **News sitemap** — B2B-ЖБИ не генерирует news-volume. Подключим если появится 5+ news/нед.
- **`redirect_to_id` self-ref** — существующая `redirects` таблица + middleware покрывает.
- **FAQ-on-every-pillar** — keyword-stuffing флаг. FAQ только при реальных Q&A из Wordstat.
- **SpeakableSpecification schema** — Google убрал поддержку 2023.
- **MySQL FULLTEXT** — нет русской морфологии. Сразу Meilisearch.
- **Google sitemap ping** — endpoint удалён июнь 2023.
- **Yandex «Оригинальные тексты» API** — закрыт ~2022.
- **`<meta name="keywords">`** — Yandex игнорирует с 2014.
- **`Organization.hasMap` для Y.Бизнес** — связка идёт через Webmaster region binding, не через schema.
- **`rel=prev/next` pagination** — Google игнорирует с 2019.
- **.gov/.edu outbound как «LLM trick»** — folk wisdom без доказательств. Делаем для honest sourcing, не trick.

**Откорректировано:**

- **Index column order**: `[blog_category_id, published_at]` (equality-first), не reverse
- **Word-count regex**: `preg_split('/[\s\p{P}]+/u')`, не `preg_match_all('/\b\w+\b/u')` — `\b` ненадёжен на Cyrillic
- **Cover image upload**: `dimensions:min_width=1200` валидация — без неё schema_* конверсии upscale до blurry
- **`BlogCategory::imageAlt()`**: описывает картинку, не страницу
- **`updated_content_at`**: read-only в форме + action-only (защита от fake-freshness)
- **`pillar_id` + `is_pillar`**: парная пара для disambig в P1
- **HowTo schema**: оставлено в плане, но переквалифицировано из «SERP rich results» в «AI extraction only» (Google убрал SERP-эффект 2023)
- **3 image ratios**: переквалифицировано из «обязательно» в «рекомендация Google» (1 image 1200+ достаточно для rich results)
- **`BlogPosting` vs `Article`**: явно проговорено — разницы для SERP нет, выбор семантический
- **Comments SEO-эффект**: «положительный, но операционная цена перевешивает», не «≈ 0»
- **Calibrated impact percentages** в §2 — реалистичные доли влияния факторов

**Добавлено:**

- Глава о calibrated SEO-приоритетах (§2)
- Disambig между BlogCategory и catalog Category (§3.3)
- Спецификация `effectiveModifiedAt()` fallback (§6.3)
- Y.Metрика Reports API workflow для popular (§16.3)
- Engineering conventions cross-ref на [CLAUDE.md](./CLAUDE.md)
