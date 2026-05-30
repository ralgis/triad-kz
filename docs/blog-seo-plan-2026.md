# Блог triad.kz — SEO-ТЗ 2026

> Живой документ. Сгенерирован по результатам deep-research-сессии
> (web-источники в §Sources). Источник правды для архитектуры блога.
> Корректировать по мере имплементации — в gap-секции отмечать ✅.
>
> **2026-05-30 — пост-критика, корректировки:**
> - Author-инфраструктура **выкатана из P0**. Решение: на старте идём в
>   **publisher-only режим** (Organization = и author и publisher в JSON-LD,
>   schema-валидно, Google поддерживает). Реального инженера-автора пока
>   нет, а фейковый byline хуже отсутствия (Spam Policies, manual action).
>   Если позже появится верифицированный эксперт — Author возвращаем в P2.
> - Несколько SEO-утверждений в этом документе помечены ⚠ **deprecated/
>   overstated** — см. inline-пометки. Реальное влияние Author-entity на
>   ранжирование 1-3%, AI-цитирование +10-15%; не центральная фича.
> - Прочие fix'ы критики: индекс `[blog_category_id, published_at]`
>   (equality-first); word-count regex через `\p{P}` (русские слова);
>   `updated_content_at` read-only в форме, меняется только action'ом;
>   `dimensions:min_width=1200` на cover; `BlogCategory.imageAlt` описывает
>   картинку а не страницу.

## 0. Стратегический контекст

**Зачем B2B-каталогу блог.** Каталог отвечает на коммерческие транзакционные
запросы («купить ФБС12.4.6», «цена бетонного кольца КС10.9»). Этого мало —
~80% поискового трафика приходит на **информационные запросы**: «как выбрать
ФБС блоки для подвала», «расшифровка маркировки бетонных колец», «расчёт
количества колец для септика», «отличия ГОСТ 8020-90 и серии 3.900.1-14»,
«марки бетона для фундамента». Блог ловит эти запросы и через внутреннюю
перелинковку гонит трафик в каталог.

**Дополнительные цели 2026:**
1. **E-E-A-T-сигнал** — Google и Yandex одинаково проверяют, что за контентом
   стоят реальные эксперты (инженер ПТО, прораб с 20-летним стажем). Без
   авторов с био-страницами блог в B2B нише не вылезает выше 5-й страницы.
2. **AI-цитируемость** — Perplexity / ChatGPT Search / Google AI Overviews
   формируют ответы. Каталог как источник они почти не используют, а статью
   «как выбрать ФБС» с структурированным FAQ и таблицей сравнения — цитируют
   охотно. Цитата → бренд → трафик.
3. **Topical authority** — Yandex с 2024 ранжирует не отдельные страницы, а
   домены по тематической компетенции. 10 связанных статей по «фундаментным
   блокам» дают каталогу больший буст, чем 10 разрозненных «про всё подряд».

## 1. Архитектура контента

### 1.1. Типы статей (article_type enum)

| Тип | Назначение | Длина | Schema |
|---|---|---|---|
| `pillar` | Pillar page — обзорная статья «всё про ФБС» / «всё про бетонные кольца», 8-12 H2-секций | 3000-5000 слов | Article + FAQPage |
| `guide` | How-to: «как выбрать», «как считать», «как монтировать» | 1500-2500 слов | Article + HowTo |
| `comparison` | Сравнения: «КС vs пластиковые кольца», «ФБС vs монолит» | 1200-2000 слов | Article |
| `news` | Новости компании, обновления каталога, выпуск новых ГОСТов | 400-800 слов | NewsArticle |
| `case` | Кейсы (объект построен с применением наших ЖБИ) | 800-1500 слов | Article + ImageGallery |

`pillar` и `guide` — ядро SEO-трафика. `news` / `case` — для прогрева бренда и E-E-A-T.

### 1.2. Категории (рубрики) — нужны

Категория = тематический хаб = pillar page + кластеры:

| Категория | Pillar | Целевой запрос Yandex Wordstat |
|---|---|---|
| Бетонные кольца | «Бетонные кольца КС: полный справочник» | "бетонные кольца" 12k/мес |
| ФБС блоки | «Фундаментные блоки ФБС: маркировка, ГОСТ, монтаж» | "фбс блоки" 8k/мес |
| Плиты перекрытия | … | "плиты перекрытия колодца" 3k/мес |
| Теплотрассы | «Лотки и опорные подушки для теплотрасс» | … |
| ГОСТы и серии | «Справочник ЖБИ ГОСТов» | "гост на жби" 2k/мес |
| Расчёты и сметы | «Калькуляторы и формулы для подсчёта ЖБИ» | … |
| Монтаж и эксплуатация | … | … |

URL: `/blog/category/{slug}`.

### 1.3. Теги — не нужны, замени на entity-связи

Теги-как-в-WP создают тысячи thin-content страниц и каннибализуют рейтинги.
Вместо тегов используй прямые M2M-связи статьи с реальными сущностями
каталога:
- `article_products` — статья ссылается на конкретные товары
- `article_gosts` — статья ссылается на ГОСТы/серии (модель `Gost` уже есть)
- `article_categories` (продуктовых) — статья ссылается на категории каталога

### 1.4. Авторы — **выкатано из плана** (publisher-only)

> **Решение 2026-05-30:** Author-инфраструктуру не делаем. ЖБИ-каталог —
> не YMYL-ниша, реальный эффект Author entity на ранжирование 1-3%, AI-
> цитирование +10-15%. Заказчик не имеет верифицированного эксперта-
> автора, а фейковый byline хуже отсутствующего.
>
> **Что вместо:** Organization (юр.лицо ТРИ АД) в JSON-LD одновременно
> как `author` и `publisher`. Google и Yandex поддерживают эту схему
> явно. Никаких ProfilePage-страниц, никакого `/blog/author/{slug}`.
>
> Если в Phase 2+ появится верифицированный инженер — Author-модель
> возвращаем. Сейчас не делаем.

## 2. Расширение модели Article

### 2.1. Новые колонки

| Колонка | Тип | Зачем |
|---|---|---|
| ~~`author_id`~~ | — | **Выкатано.** Publisher-only режим. |
| `article_type` | string (cast enum) | См. 1.1 — переносим в P0, управляет рендером |
| `pillar_id` | FK self-ref nullable | Кластер → pillar (P1) |
| `is_pillar` | bool default false | Парный к pillar_id — снимает двусмысленность «эта без pillar или сама pillar» (P1) |
| `blog_category_id` | FK → blog_categories | Рубрика |
| `subtitle` | varchar(300) nullable | Подзаголовок под H1 |
| `reading_minutes` | tinyint nullable | Авторасчёт |
| `word_count` | mediumint nullable | Тот же расчёт |
| `published_at` | (есть) | + индекс `[blog_category_id, published_at]` (equality-first) |
| `updated_content_at` | timestamp nullable | Отдельно от `updated_at`; ставится ВРУЧНУЮ через Filament action — Google карает за fake-touch. В форме поле read-only. |
| `featured` | bool | На главную /blog |
| `pinned_until` | timestamp nullable | Sticky в категории на срок |
| `toc_enabled` | bool default true | Можно отключить TOC |
| `enable_comments` | bool default false | На старте без |
| `faq` | JSON nullable | `[{question, answer}, ...]` — только если реально есть вопросы (см. §5.2) |
| `how_to_steps` | JSON nullable | `[{name, text, image_id}, ...]` — **только для AI extraction**, Google HowTo rich results deprecated 2023 |
| `external_sources` | JSON nullable | `[{title, url, accessed_at}]` |
| ~~`view_count`~~ | — | **Выкатано.** Использовать Y.Metrика API (см. §13) |
| ~~`redirect_to_id`~~ | — | **Выкатано.** Существующая Redirects-таблица + middleware решает |

### 2.2. Структура `content`

WYSIWYG (TipTap) с встроенными custom-blocks:
- `[product:slug]` — карточка товара inline (x-product-card)
- `[gost:slug]` — pill ГОСТ с tooltip
- `[note]...[/note]` / `[warning]...[/warning]` — выделенные блоки
- `[table-spec]...[/table-spec]` — таблица с микроразметкой PropertyValue
- `[summary]...[/summary]` — TL;DR (отдельно индексируется через speakable)

H2/H3 структура обязательна (для TOC и AI extraction). Один H1 = `title`.

## 3. URL-структура

```
/blog                            — главная блога: pillar'ы + featured + последние
/blog/{slug}                     — статья (slug глобально уникален, без category nesting)
/blog/category/{cat-slug}        — категория-рубрика
/blog/author/{author-slug}       — страница автора
/blog/page/2                     — пагинация (rel=prev/next + canonical на /blog)
/blog/search?q=...               — поиск (noindex, follow)
/blog/feed.rss                   — RSS 2.0
/blog/feed.atom                  — Atom
```

**Slug-конвенция:** kebab-case, латиница. Slug глобально уникален — статья
может принадлежать нескольким категориям через тематическое перекрытие;
вложение в `/blog/category/{cat}/{slug}` ломает SEO при смене категории.

Slug auto-redirect (`HasSlugRedirect` trait уже есть) — пишем row в `redirects`.

## 4. Render features

### 4.1. Страница статьи `/blog/{slug}`

Структура сверху вниз:
1. Breadcrumb (`Главная → Блог → Категория → Title`) + BreadcrumbList schema
2. Header: категория-pill, H1, subtitle, метаданные
   (published_at, updated_content_at если отличается, reading_minutes, word_count).
   **Без author byline** (publisher-only режим).
3. TL;DR блок (если `[summary]` в content)
4. Cover image — `<figure>` с `<figcaption>`, srcset для 3 размеров
5. TOC — авто из H2/H3 (sticky на desktop сбоку)
6. Content — sanitized HTML с heading-anchors
7. FAQ блок (если `faq` реально заполнен — органические вопросы из Wordstat,
   не «дополним для schema»). `<details>` + FAQPage JSON-LD.
8. Источники (если `external_sources` непустое) — `rel="noopener external"`
9. ~~Author bio block~~ — выкатано
10. Related (2 блока в P0): same blog_category; pillar/cluster — в P1
11. CTA: «Связаться с инженером» / «Смотреть каталог»
12. Social share (VK / Telegram / WhatsApp, без JS-трекеров)

### 4.2. Расчёт reading_minutes / word_count

При сохранении (Model observer):
```php
$plain = strip_tags($this->content);
$words = preg_match_all('/\b\w+\b/u', $plain);
$this->word_count = $words;
$this->reading_minutes = max(1, (int) ceil($words / 180));  // 180 wpm для технического
```

### 4.3. updated_content_at — НЕ авто

Filament-кнопка «Пометить статью обновлённой» = устанавливает
`updated_content_at = now()`. Google карает fake-touch (см. Helpful Content
Update 2023+).

### 4.4. Страница `/blog`

- Hero: pillar категории "ГОСТы и серии" или последний pillar
- Featured (3-6, `featured=true`)
- Pillars grid (по 1 на категорию)
- Последние (paginated 12 на стр)
- Sidebar: «Популярные» (sort by view_count), список авторов

### 4.5. Страница категории `/blog/category/{cat}`

- H1 = название
- Description (WYSIWYG) — pillar-style 300-500 слов
- Pinned/sticky (`pinned_until > now()`) первыми
- Featured затем
- Остальные по `published_at DESC`
- Schema: CollectionPage + BreadcrumbList

### 4.6. ~~Страница автора~~ — **выкатано**

Publisher-only режим. Никакой `/blog/author/{slug}` страницы.

## 5. Schema.org markup

### 5.1. BlogPosting (расширь существующий)

> ⚠ Note: 3 image ratios (1:1, 4:3, 16:9) — **рекомендация Google**, не
> требование. Достаточно одного 1200+ image для rich results eligibility.
> Делаем 3 ratios потому что они дёшевы (одна Spatie-conversion + диск).
>
> ⚠ Note: `BlogPosting` vs `Article` — **разницы для SERP нет**. Google
> явно: они share identical required/recommended properties. Выбираем
> BlogPosting для семантической точности (контент = блог, не
> энциклопедия).
>
> ⚠ `author` = Organization (та же сущность что publisher) — Google
> поддерживает явно. Никакого Person entity.

```json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "@id": "https://triad.kz/blog/{slug}#article",
  "headline": "...",
  "alternativeHeadline": "{subtitle}",
  "description": "...",
  "image": [
    "{cover 1:1 url}",
    "{cover 4:3 url}",
    "{cover 16:9 url}"
  ],
  "datePublished": "ISO8601",
  "dateModified": "ISO8601",
  "author":    { "@id": "https://triad.kz/#organization" },
  "publisher": { "@id": "https://triad.kz/#organization" },
  "mainEntityOfPage": { "@type": "WebPage", "@id": "{url}" },
  "wordCount": 2300,
  "articleSection": "{blog_category.name}",
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

### 5.2. FAQPage (опц, **только когда реально 4-8 живых вопросов**)

> ⚠ Google scaled back FAQ rich results в 2023 — показывает FAQ
> snippets очень селективно. Yandex не показывает вообще. **Schema
> добавляем не ради SERP-эффекта**, а ради AI extraction (Perplexity/
> ChatGPT кушают `mainEntity[Question/Answer]` напрямую).
>
> Раскатывать FAQ на КАЖДОМ pillar ради schema — keyword-stuffing
> флаг. Только когда есть реальные вопросы из Wordstat / Search Console.
>
> Контент вопросов ОБЯЗАН быть на странице (`<details>`).

### 5.3. HowTo (опц, **только для AI extraction**)

> ⚠ Google **удалил HowTo rich results** из SERP в августе 2023 (mobile
> и desktop). Старые кейсы с «+20% CTR» — до-2023-го артефакт.
>
> Schema валидна, для Perplexity/ChatGPT полезна. Но как SEO-фича —
> мертва. Делаем как P2.

### 5.4. BreadcrumbList (на каждой странице кроме home/category-index)

### 5.5. ~~ProfilePage~~ — выкатано (нет автора)

### 5.6. Organization (singleton, глобальный) — `@id` граф

`@id: "https://triad.kz/#organization"`. BlogPosting.author и
.publisher оба ссылаются по `@id`.

## 6. Open Graph + Twitter

| Поле | Откуда |
|---|---|
| `og:title` | `meta_title \|\| title` |
| `og:description` | `meta_description \|\| excerpt` |
| `og:image` | cover 1200×630 (Spatie 'og') |
| `og:type` | `article` |
| `og:locale` | `ru_RU` |
| `og:site_name` | site_name |
| `article:published_time` | ISO datePublished |
| `article:modified_time` | ISO dateModified |
| ~~`article:author`~~ | — (publisher-only) |
| `article:section` | название категории |
| `article:tag` | gosts.label + связанные товары (3-5) |
| `twitter:card` | `summary_large_image` |

## 7. Internal linking — topic clusters

1. **Кластер ссылается на pillar** — авто-блок «Часть pillar [X]» внизу
2. **Pillar ссылается на ВСЕ кластеры** — авто-блок «В этой теме»
3. **Anchor text** — title статьи, никаких generic «подробнее»
4. **Каждая статья → 2-5 товаров** через `article_products`
5. **Каждая статья → 1-3 ГОСТа** через `article_gosts`
6. **Минимум 2 inbound внутренних ссылки** на каждую статью. Filament
   ArticleResource показывает счётчик `inbound_count`.

## 8. Yandex-специфика (KZ)

### 8.1. IndexNow protocol

Pakage `ymigval/laravel-indexnow` или свой post-save observer:
- При publish/update/delete → POST на `api.indexnow.org/IndexNow`
- Yandex+Bing получают пинг, обход за 15 мин (vs 2-14 дней)
- Google НЕ поддерживает — для него отдельно sitemap re-ping

### 8.2. Yandex.Webmaster

- Региональность → Алматы, KZ (через Y.Бизнес-карточку)
- ~~Оригинальные тексты API~~ — **API закрыт ~2022**. Claim-authorship
  идёт через IndexNow (Yandex стампит время первого обнаружения).
- Sitemap submission через Webmaster UI или sitemap-link в robots.txt

### 8.3. Meta-теги (Yandex чувствителен к canonical)

- `<meta name="description">` обязателен ≤160 chars
- ~~`<meta name="keywords">`~~ — **Yandex игнорирует с 2014**. Не делаем.
- `<meta name="robots">` чётко
- `<link rel="canonical">` обязателен — Yandex борется с дублями жёстче

### 8.4. Yandex.Бизнес-интеграция

> ⚠ `Organization.hasMap` НЕ привязывает к Y.Бизнес. Это schema-поле
> для embedded-карты (любой карты). Реальный механизм связки:
> Yandex Webmaster → Регион Алматы → Y.Бизнес-карточка → верификация
> домена. Делается **в UI Y.Webmaster**, не через schema.org.

## 9. AI search optimization (GEO/AEO)

### 9.1. Структура под extraction

- Definitions on top (1-2 параграфа в начале guide/pillar)
- TL;DR блок (`[summary]`) + speakable schema
- Цифры в таблицах, не в прозе
- Named entities явно (организация, автор+должность, ГОСТ)
- Дата свежести: Perplexity взвешивает <90 дней выше

### 9.2. FAQ — long-tail формулировки

❌ «Что такое ФБС?»
✅ «Чем отличается ФБС12.5.6-Т от ФБС24.4.6-Т?»

### 9.3. External authoritative outbound

- gosstandart.gov.kz, rosstandart.gov.ru, nostroy.ru
- `rel="external nofollow noopener"`

### 9.4. ~~SpeakableSpecification~~ — Google убрал поддержку 2023

> Schema валидна, но Google Assistant rich results — мёртвые с 2023.
> AI extraction не требует speakable. **Не делаем.**

## 10. Sitemap + индексация

- Article: priority=0.6, changefreq=monthly, lastmod=updated_content_at??published_at
- BlogCategory: priority=0.7, changefreq=weekly
- ~~Author sitemap~~ — выкатано
- ~~News sitemap~~ — **выкатано** (B2B-ЖБИ блог не генерирует news-volume,
  пустой news-sitemap = шум)
- IndexNow ping на publish/update (Yandex+Bing)
- ~~Google sitemap ping endpoint~~ — **удалён Google июнь 2023**, не
  пингуем. Полагаемся на естественный обход + Search Console submit.

## 11. Поиск `/blog/search`

> ⚠ MySQL FULLTEXT **не работает на русском** из коробки — нет
> морфологии, «бетон» не найдёт «бетонный». Сразу делаем Meilisearch
> с русскими analyzers (один Docker-контейнер). False-economy
> «MySQL до 500 статей» — переписываем потом дороже.

- Meilisearch с русскими analyzers (`stop-words.json` + lang=ru)
- Индексация Article.title + excerpt + content + blog_category.name
- Page `/blog/search?q=...`: `noindex, follow`
- Highlight matched terms

## 12. Performance / Core Web Vitals

- LCP ≤ 2.5s — cover `<img fetchpriority="high">`, preconnect Метрика/GA
- CLS ≤ 0.1 — все `<img>` с width/height, fonts `font-display:swap` + preload
- INP ≤ 200ms — TipTap content без JS-heavy блоков
- WebP+JPEG fallback через `<picture>` (Spatie + Glide)

## 13. Engagement & analytics

### Метрика-события (Y.Метрика + GA4) — Alpine.js

- `article_view`, `article_read_25pct/50/75/100`
- `article_toc_click`, `article_faq_open`
- `article_product_click`, `article_share_click`, `article_cta_click`

### ~~View counter в БД~~ — выкатано

> ⚠ `UPDATE articles SET view_count = view_count + 1` каждый запрос =
> БД-нагрузка + race conditions. Cookie-debounce обходится curl'ом.
>
> **Правильно:** «популярные» брать из **Yandex.Metrика Reports API**
> раз в N часов в кэш. Считаем там — там уже есть бот-фильтр и
> уникальные пользователи.

## 14. Комментарии

На старте — НЕ делаем. **Не потому что SEO-эффект ноль** — он не ноль
(engagement сигналы у Yandex значимы), а потому что операционная цена
модерации без оперативного админа перевесит выгоду. Позже — Disqus/
Hyvor с lazy-load, когда появится модератор.

## 15. Filament SEO-блок (UX для редактора)

Живые подсказки помимо meta_title/desc:
- Word count + reading time (авто)
- Title length indicator (50-60 ✅, 61-70 ⚠, >70 ❌)
- Description length indicator (140-160 ✅)
- Inbound link count
- Outbound product links count
- Has author/category/cover (чек-лист)
- FAQ filled (для pillar/guide)
- Image alts filled
- Has TL;DR (`[summary]` в content)
- Date staleness — если `updated_content_at` > 6 мес, warning

## 16. `/blog` как hub

- WebSite + Organization schema (один раз, `@id`)
- CollectionPage schema
- SearchAction schema:
  ```json
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://triad.kz/blog/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
  ```

---

## 17. План реализации по фазам

### Phase 1 — P0 (foundation, ~3 дня)

- [x] 1.1. ~~Миграция `create_authors_table`~~ — выкатано
- [x] 1.2. Миграция `create_blog_categories_table` ✅
- [x] 1.3. Миграция `add_seo_fields_to_articles_table` (blog_category_id, subtitle, reading_minutes, word_count, updated_content_at) ✅
- [x] 1.4. Модель BlogCategory + расширение Article (author убрана) ✅
- [x] 1.5. Article::booted() saving-hook (word_count + reading_minutes) ✅
- [x] 1.6. ~~Filament AuthorResource~~ — выкатано
- [x] 1.7. Filament BlogCategoryResource ✅
- [x] 1.8. Filament ArticleResource — поля + action «Пометить обновлённой» ✅
- [ ] 1.9. Route /blog/category/{slug}
- [ ] 1.10. BlogCategoryController (или extend BlogController)
- [ ] 1.11. ~~View blog/author.blade.php~~ — выкатано
- [ ] 1.12. View blog/category.blade.php
- [ ] 1.13. Update blog/article.blade.php: breadcrumb + reading time + updated date + related-by-category (без author byline)
- [ ] 1.14. Service ContentToc (parse + inject H2/H3 ids) + TOC nav
- [x] 1.15. Spatie conversions schema_1_1/schema_4_3/schema_16_9 ✅
- [ ] 1.16. Расширить schema/article.blade.php — Organization как author+publisher, @id граф, 3 image ratios, wordCount, articleSection
- [ ] 1.17. ~~schema/profile-page.blade.php~~ — выкатано
- [ ] 1.18. schema/blog-collection.blade.php (для категории)
- [ ] 1.19. Глобальный schema/organization.blade.php с @id
- [ ] 1.20. BreadcrumbList JSON-LD на статье + категории

### Phase 2 — P1 (~3-4 дня)

- [ ] 2.1. Миграция add pillar_id + article_type + faq + featured + pinned_until + view_count + toc_enabled
- [ ] 2.2. Pivot `article_products` (M2M) + UI в Filament
- [ ] 2.3. Pivot `article_gosts` (M2M) + UI в Filament
- [ ] 2.4. Pivot `article_categories` (продуктовые) + UI в Filament
- [ ] 2.5. FAQ JSON Repeater в ArticleResource
- [ ] 2.6. FAQ render блок + FAQPage JSON-LD
- [ ] 2.7. Pillar/cluster auto-блоки в views
- [ ] 2.8. IndexNow integration (post-save observer + key file)
- [ ] 2.9. Sitemap: добавить articles + blog_categories + authors
- [ ] 2.10. Filament SEO-чек-лист на форме статьи (live indicators)
- [ ] 2.11. View counter middleware + debounce cookie
- [ ] 2.12. Featured/pinned ordering в views

### Phase 3 — P2/P3 (~2-3 дня)

- [ ] 3.1. Миграция add how_to_steps + external_sources + redirect_to_id
- [ ] 3.2. HowTo JSON-LD + render
- [ ] 3.3. external_sources render внизу
- [ ] 3.4. TL;DR `[summary]` parser + speakable schema
- [ ] 3.5. RSS + Atom feeds
- [ ] 3.6. News sitemap (если делаем article_type='news')
- [ ] 3.7. Метрика scroll/TOC/FAQ/share events (Alpine.js)
- [ ] 3.8. redirect_to_id 301 (для удалённых/слитых)
- [ ] 3.9. Search FULLTEXT + `/blog/search` view

---

## Gap analysis — текущее состояние

### Что уже есть
- ✅ Article: title, slug, excerpt, content, published_at, cover, SEO trait (meta_title/desc/canonical/noindex/structured_data_override), softDeletes, slug auto-redirect
- ✅ Базовый Article JSON-LD ([resources/views/partials/schema/article.blade.php](../resources/views/partials/schema/article.blade.php))
- ✅ Image alt (`imageAlt()` / `imageTitle()` helpers — commit d661a043)
- ✅ Open Graph base в [partials/head.blade.php](../resources/views/partials/head.blade.php)
- ✅ Spatie media: cover (single, thumb/card/og/hero)

### Что добавляется в Phase 1 P0
См. чек-лист Phase 1 выше.

---

## Sources

- [Technical SEO Checklist 2026 | DebugBear](https://www.debugbear.com/blog/technical-seo-checklist)
- [Article Vs Blog Schema 2026](https://searchenginezine.com/technical/schema/article-vs-blog-schema/)
- [Article Schema | Google Search Central](https://developers.google.com/search/docs/appearance/structured-data/article)
- [Blog Schema Markup Guide 2026 | Superblog](https://superblog.ai/blog/blog-schema-markup-guide/)
- [SEO Content Clusters 2026: Topic Authority Guide](https://www.digitalapplied.com/blog/seo-content-clusters-2026-topic-authority-guide)
- [Internal Linking Strategy: B2B SEO](https://kerkarmedia.com/internal-linking-strategy-b2b-seo/)
- [Enhance B2B SEO With Content Clusters: 2026](https://tayaagency.com/b2b-seo-strategy-2026/)
- [Yandex SEO Guide: Russian & CIS](https://www.arjankc.com.np/blog/yandex-seo-guide-optimization/)
- [Optimizing for Yandex: comprehensive guide](https://www.weglot.com/blog/yandex-seo)
- [IndexNow Documentation](https://www.indexnow.org/documentation)
- [Laravel IndexNow integration](https://banatube.medium.com/supercharge-your-laravel-seo-with-indexnow-real-time-search-engine-notifications-f30726773657)
- [FAQ Schema: When It Helps in 2026](https://xugar.com.au/blog/faq-schema/)
- [Тренды SEO для B2B 2026 | ADPASS](https://adpass.ru/trendy-seo-dlya-b2b-v-2026-godu-kak-uvelichit-organicheskij-trafik-sajta/)
- [SEO в Яндексе 2026 гайд](https://lpmotor.ru/articles/seo-yandex-2026-polnyj-gajd-2603)
- [GEO: Citations in ChatGPT/Perplexity 2026](https://www.aimagicx.com/blog/generative-engine-optimization-chatgpt-perplexity-2026)
- [Answer Engine Optimization Guide 2026 | Frase](https://www.frase.io/blog/what-is-answer-engine-optimization-the-complete-guide-to-getting-cited-by-ai)
