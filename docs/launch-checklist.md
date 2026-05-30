# triad.kz — чек-лист ввода в эксплуатацию

> Последовательность действий **после deploy кода на prod** (момент когда
> сайт перестаёт быть «dev» и становится «production»). Это разовая
> операция. Каждый блок — независимый, можно делать параллельно
> разным людям.
>
> **Статус-нотация:** ⬜ не сделано · 🔄 в процессе · ✅ сделано
>
> Версия чек-листа: 1.0 (2026-05-30).

---

## 1. Production cutover (домен + env)

Это **первое**, что делается. До этого блока сайт лежит на dev.triad.kz
с basic-auth-фенсом. После — он на triad.kz открыт миру.

### 1.1. Plesk: переименование поддомена ⬜
- Plesk → Хостинг и DNS → выбрать `dev.triad.kz`
- «Изменить имя домена» → `triad.kz`
- DNS-записи остаются на тех же серверах (NS не меняем)
- Проверить что https://triad.kz открывается (Let's Encrypt SSL должен перенестись автоматически; если нет — Plesk → SSL/TLS → выпустить заново)

### 1.2. Plesk: снять HTTP basic auth ⬜
- Plesk → «Защищённые папки» → удалить правило на корне triad.kz
- Иначе поисковики получают 401 и не индексируют сайт

### 1.3. Переключить APP_ENV на production ⬜
- Plesk → File Manager → `.env` файл
- Изменить:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://triad.kz
  ```
- Plesk Laravel Toolkit → выполнить команды (по одной):
  ```
  config:cache
  route:cache
  view:cache
  filament:cache-components
  optimize
  ```

### 1.4. Проверить что middleware noindex выключился ⬜
- Открыть https://triad.kz в браузере → View Source
- НЕ должно быть `<meta name="robots" content="noindex, nofollow">`
- НЕ должно быть `X-Robots-Tag: noindex, nofollow` в response headers
  (можно проверить через DevTools → Network → главная страница)
- Если есть — значит `.env` не подхватился, сделать `config:clear` и
  перезапустить через `config:cache`

### 1.5. Проверить robots.txt ⬜
- https://triad.kz/robots.txt должен показать:
  ```
  User-agent: *
  Disallow:
  Sitemap: https://triad.kz/sitemap.xml
  ```
- Если показывает `Disallow: /` — APP_ENV не production (см. 1.3)

### 1.6. Проверить sitemap.xml ⬜
- https://triad.kz/sitemap.xml → должен открыться валидным XML
- Содержит: главная, /catalog, продукты, категории каталога, /blog,
  блог-рубрики, статьи, страницы
- Валидатор: https://www.xml-sitemaps.com/validate-xml-sitemap.html

---

## 2. Google — регистрация и привязка

### 2.1. Google Search Console ⬜
- Открыть https://search.google.com/search-console
- «Add property» → ввести `https://triad.kz`
- Способ верификации (любой удобный):
  - **HTML-файл** — скачать file и положить в `public/{filename}.html` через Plesk File Manager
  - **DNS TXT-запись** — Plesk → DNS → добавить TXT-запись (рекомендую этот способ, он переживает редеплои)
  - **HTML-тег** — добавить в `<head>` через layout (хуже — нужен deploy кода)
- После верификации:
  - **Sitemaps** → ввести `sitemap.xml` → Submit
  - **URL Inspection** → ввести `https://triad.kz` → «Request indexing»
  - Поставить **email уведомления** о критичных ошибках

### 2.2. Google Analytics 4 (GA4) ⬜
- Открыть https://analytics.google.com
- «Create property» → имя `triad.kz`, страна Казахстан, валюта KZT
- Создать data stream → веб → ввести `https://triad.kz` → получить
  **Measurement ID** (формат `G-XXXXXXXXXX`)
- В админке нашего сайта:
  - `/admin/scraper-settings` (или Settings → Аналитика)
  - Поле «Google Analytics ID» → вставить `G-XXXXXXXXXX`
  - Toggle «Analytics enabled» → включить
- Проверить через DevTools → Network → отфильтровать `g/collect` или `gtag` — должны видеть запросы при загрузке страниц

### 2.3. Google Business Profile (Google Бизнес) ⬜
- Открыть https://business.google.com
- Создать профиль:
  - Название: «ТРИ АД Construction» (точное юр.имя)
  - Категория: «Поставщик железобетонных изделий» / «ЖБИ»
  - Адрес: уточнить у заказчика, ввести точный
  - Телефон: основной номер из Settings
  - Сайт: `https://triad.kz`
- Верификация: Google пришлёт код **обычной почтой** (открытка) — занимает 7-14 дней
- После верификации:
  - Добавить часы работы (синхронизировать с теми что в Settings нашей админки)
  - Загрузить логотип + 5-10 фото объектов / производства
  - Это даёт **local pack** в Google Maps по запросам типа «ЖБИ Алматы»

---

## 3. Yandex — регистрация и привязка

### 3.1. Yandex.Webmaster ⬜
- Открыть https://webmaster.yandex.ru
- «Добавить сайт» → ввести `https://triad.kz`
- Верификация (рекомендую DNS, как с Google):
  - **DNS TXT** — Plesk → DNS → добавить TXT-запись по инструкции
  - или HTML-файл / meta-тег
- После верификации:
  - **Индексирование → Sitemap** → добавить `https://triad.kz/sitemap.xml`
  - **Индексирование → IndexNow** → должна **автоматически появиться запись**
    о нашем ключе (наш `IndexNowKeyController` уже работает)
  - Если IndexNow не видит — проверить `/{key}.txt` отдаётся 200 OK
    (взять ключ из Settings → IndexNow Key)
  - **Региональность** → выбрать «Алматы» (требует Y.Бизнес профиль, см. 3.3)
  - **Поисковые запросы** → подключить (бесплатно)

### 3.2. Yandex.Metrika ⬜
- Открыть https://metrika.yandex.com
- «Добавить счётчик»:
  - Имя: «triad.kz»
  - Адрес сайта: `triad.kz`
  - Часовой пояс: Asia/Almaty (UTC+5)
  - Включить **Webvisor** (запись сессий — увидим где пользователи бросают чтение блога)
- Скопировать **Счётчик ID** (формат `12345678`, 8 цифр)
- В админке:
  - Settings → «Yandex Metrika ID» → вставить
  - Toggle «Analytics enabled» включить
- Проверить через 30 минут: Metrika → счётчик → должны появиться визиты

### 3.3. Yandex.Бизнес ⬜
- Открыть https://yandex.com/business
- Создать профиль организации:
  - Название, адрес, телефон, часы работы — из нашей Settings
  - Категория: ЖБИ / стройматериалы
  - Загрузить логотип + фото
- Привязать сайт: указать `triad.kz` → подтвердить через Webmaster (это
  same-Yandex-account → автоматически)
- После подтверждения:
  - В Y.Webmaster → Региональность → «Алматы» становится доступной
  - Карточка появляется в **Яндекс.Картах** + **локальной выдаче** для
    запросов типа «ЖБИ Алматы»

### 3.4. Yandex Metrika OAuth-токен (для блока «Популярное») ⬜
- Открыть https://oauth.yandex.com под аккаунтом счётчика
- «Зарегистрировать новое приложение»:
  - Платформа: «Веб-сервисы»
  - Callback URI: `https://oauth.yandex.com/verification_code`
  - Права (обязательно): «Яндекс.Метрика → Получение статистики»
- После регистрации скопировать **отладочный токен** (не client_id, а сам **токен** — формат `y0_AgAA...`)
- В админке:
  - Settings → «Yandex Metrika OAuth token» → вставить
- Включить cron `blog:refresh-popular` (см. §7.2)

---

## 4. 2GIS

### 4.1. Карточка организации 2GIS ⬜
- Открыть https://2gis.kz → найти «ТРИ АД Construction» (если не нашлось — добавить через https://my.2gis.kz)
- Заполнить:
  - Все контакты, часы работы, сайт, фото
  - Категория: «Железобетонные изделия»
- 2GIS в Казахстане **доминирует** для poiск-по-карте — для B2B-клиентов
  это первичный канал когда они открывают карту искать поставщика

### 4.2. 2GIS Виджет на сайте (опц) ⬜
- На странице /contacts можно вставить 2GIS-карту через iframe
- Готовый embed-код в Личном кабинете 2GIS

---

## 5. Контент — финальный проход перед открытием

### 5.1. Wordstat-аудит ключевых запросов ⬜

Цель: получить **настоящие** частотности для приоритезации контент-плана.

- Открыть https://wordstat.yandex.ru (логин в Яндекс)
- Регион → **Казахстан** (или конкретно «Алматы» если хотим только местный спрос)
- Для каждой рубрики блога вбить запросы:

| Рубрика | Запрос для проверки | Записать число |
|---|---|---|
| Бетонные кольца | бетонные кольца | ___ /мес |
| Бетонные кольца | колодезные кольца | ___ /мес |
| ФБС блоки | фбс блоки | ___ /мес |
| ФБС блоки | фундаментные блоки | ___ /мес |
| Плиты перекрытия | плиты перекрытия колодца | ___ /мес |
| Теплотрассы | лотки теплотрассы | ___ /мес |
| ГОСТы и серии | гост 8020-90 | ___ /мес |
| Расчёты | как рассчитать кольца для септика | ___ /мес |
| Монтаж | монтаж колодца из колец | ___ /мес |

- Записать **в файл** `docs/blog/PLAN.md` секция §3.3 — заменить «TBD» на реальные цифры
- На основании цифр **переприоритезировать порядок написания pillar-статей**

### 5.2. Backfill контент-санитизации ⬜
В блоге могут остаться импортированные статьи со старого WP-сайта.
HTMLPurifier не вызывался на их content при импорте — прогнать сейчас:

- Plesk Laravel Toolkit:
  ```
  articles:resanitize-content --dry-run
  ```
- Посмотреть на цифру «N/M articles had markup adjusted». Если N
  разумное (не «все 50» — что бы значило что purifier слишком агрессивен), запустить **без** `--dry-run`:
  ```
  articles:resanitize-content
  ```

### 5.3. Авто-перелинковка «С этим товаром покупают» ⬜
- Plesk Laravel Toolkit:
  ```
  products:auto-complement --dry-run
  ```
- В выводе посмотреть таблицу «Товар → Спутники». Проверить визуально:
  - Каждый товар получил 4-6 спутников?
  - Спутники соответствуют здравому смыслу (кольцо → плиты колодца, не
    кольцо → ФБС блок)?
- Если устраивает — запустить **без** `--dry-run`
- Если не устраивает — сказать AI (мне), скорректируем `RELATED_CATEGORIES`
  в команде

### 5.4. Image alt-текст ручной просмотр ⬜
- В админке открыть `/admin/products` → 10-15 произвольных товаров →
  проверить что cover-фото имеет осмысленный alt
- Для блог-статей — то же самое
- Helpers генерят alt автоматически из `title` + specs, но **отдельные
  случаи** (старые фото без specs) могут быть пустыми — поправить вручную

### 5.5. Meta title/description главной + рубрик ⬜
- Открыть админку → Settings → SEO главной — проверить что text актуальный
- Открыть Categories → SEO для каждой категории каталога — посмотреть
  что нет пустых
- То же для BlogCategories блога

---

## 6. Email + SMTP

### 6.1. Plesk SMTP настроить ⬜
- Plesk → Mail → создать ящик `noreply@triad.kz` (для отправки)
- Plesk → Mail → создать ящик `orders@triad.kz` (или какой решит заказчик — для получения заявок)
- `.env`:
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.triad.kz       # обычно localhost через Plesk
  MAIL_PORT=587
  MAIL_USERNAME=noreply@triad.kz
  MAIL_PASSWORD=...             # пароль ящика
  MAIL_ENCRYPTION=tls
  MAIL_FROM_ADDRESS=noreply@triad.kz
  MAIL_FROM_NAME="ТРИ АД Construction"
  ```
- Settings → «Email для получения заявок» → `orders@triad.kz`
- `config:cache`

### 6.2. SPF / DKIM / DMARC DNS-записи ⬜
Без этого письма уходят в спам у Mail.ru / Yandex.Mail / Gmail.

- Plesk → Mail → DKIM включить → автоматически добавит TXT-запись `_domainkey`
- Plesk → DNS → добавить SPF:
  ```
  TXT @ "v=spf1 mx ip4:<plesk-ip> ~all"
  ```
- Plesk → DNS → добавить DMARC:
  ```
  TXT _dmarc "v=DMARC1; p=quarantine; rua=mailto:postmaster@triad.kz"
  ```

### 6.3. Тестовая заявка ⬜
- Открыть `/contacts` → отправить тест-форму
- Проверить что письмо упало на `orders@triad.kz`
- Открыть `/catalog` → положить товар в корзину → checkout → подтвердить
- Проверить:
  - Письмо клиенту с подтверждением заказа упало
  - Письмо админу с уведомлением о заказе упало
  - Если заказ юрлица (БНО) → проверить PDF-счёт прикреплён

---

## 7. Cron jobs (планировщик задач)

### 7.1. Laravel scheduler ⬜
Это **обязательно** — без него не работает ни одна периодическая задача.

- Plesk → Запланированные задачи → Добавить:
  - Тип: «Запустить команду»
  - Команда: `cd /var/www/vhosts/triad.kz/httpdocs && php artisan schedule:run >> /dev/null 2>&1`
  - Расписание: **каждую минуту** (`* * * * *`)

### 7.2. Periodic blog popular refresh ⬜
- Plesk → Запланированные задачи → Добавить:
  - Тип: «Запустить команду» 
  - Команда: `cd /var/www/vhosts/triad.kz/httpdocs && php artisan blog:refresh-popular >> /dev/null 2>&1`
  - Расписание: **каждые 4 часа** (`0 */4 * * *`)

### 7.3. Backups (Spatie Backup) ⬜
Если пакет `spatie/laravel-backup` установлен:
- Plesk → Запланированные задачи:
  - Команда: `cd /var/www/vhosts/triad.kz/httpdocs && php artisan backup:run >> /dev/null 2>&1`
  - Расписание: **каждый день в 4 утра** (`0 4 * * *`)
- Очистка старых бэкапов:
  - Команда: `cd /var/www/vhosts/triad.kz/httpdocs && php artisan backup:clean`
  - Расписание: **раз в неделю** (`0 5 * * 0`)
- В `config/backup.php` указать destination (локальная папка вне httpdocs ИЛИ Plesk Cloud Backup ИЛИ S3-совместимое хранилище). Локально хранить **минимум 3 поколения**.

### 7.4. Альтернатива: всё через Laravel scheduler ⬜
Вместо отдельных cron-записей можно в `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('blog:refresh-popular')->everyFourHours();
Schedule::command('backup:run')->dailyAt('04:00');
Schedule::command('backup:clean')->weekly();
```

Тогда нужен только один cron (§7.1). **Рекомендую этот вариант** — проще
управлять, не лазить в Plesk при изменениях.

---

## 8. 301-редиректы со старого WP-сайта

### 8.1. Сбор старых URL ⬜
- Загрузить https://www.screamingfrogseoextract.com (free до 500 URL)
- Натравить на **старый** triad.kz (если он ещё работает)
- Экспортировать в CSV: список всех URL ответивших 200

### 8.2. Маппинг old → new ⬜
- Открыть Excel-файл с CSV
- Для каждого старого URL вписать новый (соответствующая категория /
  товар / страница на новом сайте)
- Если соответствия нет (например, удалили категорию) — указать
  «ближайшая категория» или /catalog
- Сохранить в формате CSV: `from,to,status` (status = 301)

### 8.3. Импорт в админку ⬜
- Plesk Laravel Toolkit:
  ```
  triad:import-legacy-redirects /path/to/redirects.csv
  ```
- Или через админку: Admin → Redirects → импортировать CSV
- Проверить 5-10 случайных старых URL: открыть → должны редиректить на новые

---

## 9. Performance baseline + мониторинг

### 9.1. Lighthouse baseline ⬜
- Open Chrome DevTools → Lighthouse tab
- Запустить на главной, /catalog, /catalog/{категория}/{товар},
  /blog/{статья} — **mobile**
- Записать цифры в `docs/launch-baseline.md` (создать):
  - Performance, Accessibility, Best Practices, SEO scores
  - LCP, CLS, INP
- Это **бейзлайн** — через 3-6 мес перепроверить, посмотреть деградацию

### 9.2. Search Console Core Web Vitals ⬜
- В Google Search Console через 28 дней появится «Core Web Vitals» отчёт
  с реальными данными пользователей
- Поставить **email-уведомления** о падении WV-показателей

### 9.3. Yandex.Webmaster — Качество сайта ⬜
- Включить отчёт «Качество сайта»
- Включить отчёт «Карточки товаров» (если они применимы для нашего каталога)

### 9.4. UptimeRobot или аналог ⬜
- Открыть https://uptimerobot.com (free до 50 мониторов)
- Добавить:
  - GET https://triad.kz/ — каждые 5 минут
  - GET https://triad.kz/up — каждые 5 минут (Laravel health endpoint)
- Уведомления на email/Telegram при downtime

---

## 10. Legal pages

Без этого нельзя нормально стартовать.

### 10.1. Политика конфиденциальности ⬜
- Создать страницу `/privacy-policy` (через `/admin/pages`)
- Содержание: какие данные собираем (cookies, IP, формы), для чего,
  храним сколько, контакт для удаления данных
- Если есть юрист заказчика — пусть проверит. Иначе — взять шаблон
  для KZ (например, на https://www.rusprivacy.kz)

### 10.2. Пользовательское соглашение ⬜
- Страница `/terms-of-service`
- Содержание: условия использования сайта, оферта на покупку (если
  применима), ответственность сторон, гарантии товара

### 10.3. Согласие на обработку персональных данных в формах ⬜
- На /contacts и /checkout — checkbox «Согласен на обработку перс. данных»
  + ссылка на политику конфиденциальности
- Без галочки форма не отправляется

### 10.4. Cookie consent (опц) ⬜
- В Казахстане GDPR-style cookie banner НЕ обязателен по закону
- Но если планируется EU-аудитория — добавить cookieconsent.com или
  собственный banner

---

## 11. Финальная проверка (smoke перед открытием)

### 11.1. Полный обход ключевых страниц ⬜
В режиме инкогнито (без admin-сессии):
- ⬜ Главная — открывается, загружается быстро
- ⬜ /catalog — список категорий
- ⬜ /catalog/{категория} — список товаров категории
- ⬜ /catalog/{категория}/{товар} — карточка товара, breadcrumb правильный, фото видны, «Связанные товары» заполнены
- ⬜ /blog — список статей + рубрики в sidebar
- ⬜ /blog/{статья} — статья открывается, breadcrumb правильный, reading time показано, related-блок есть
- ⬜ /blog/category/{рубрика} — список статей рубрики
- ⬜ /blog/feed.rss — валидный XML
- ⬜ /contacts — карта 2GIS видна, форма работает
- ⬜ /sitemap.xml — все URL
- ⬜ /robots.txt — Allow

### 11.2. Schema.org валидация ⬜
- Открыть https://search.google.com/test/rich-results
- Вбить URL каждой из:
  - Главная — Organization detected
  - Карточка товара — Product + BreadcrumbList detected
  - Статья — BlogPosting + Breadcrumb (+ FAQPage если есть) detected
  - Рубрика — CollectionPage detected
- НЕ должно быть **ошибок** (warnings OK для optional fields)

### 11.3. Google Rich Results Test ⬜
- Альтернативная проверка через тот же инструмент
- Зафиксировать какие rich results получает каждая страница

### 11.4. Manually отправить sitemap в обе панели ⬜
- Google Search Console → Sitemaps → Add → `sitemap.xml` → Submit
- Yandex Webmaster → Индексирование → Файлы Sitemap → Добавить →
  `https://triad.kz/sitemap.xml`
- Через 24-48 часов проверить статус «Прочитан без ошибок»

### 11.5. IndexNow проверка ⬜
- В Yandex Webmaster → Индексирование → IndexNow → должна появиться
  запись о нашем ключе
- Через сутки опубликовать тестовую статью в блоге → через 15-30 мин
  проверить что Yandex её увидел («Страницы в поиске» в Webmaster)

---

## 12. Post-launch follow-up (через 1-2 недели)

### 12.1. Анализ 404 ⬜
- Google Search Console → Покрытие → «Не найдено (404)»
- Yandex Webmaster → Индексирование → Страницы исключены из поиска (404)
- Если есть массовые 404 от внешних ссылок — добавить редиректы (см. §8)

### 12.2. Анализ Search Queries ⬜
- Google Search Console → Эффективность → за последние 28 дней
- Yandex Webmaster → Поисковые запросы → Топ запросов
- Сверить с Wordstat (§5.1) — какие реально приводят
- Использовать как input для **контент-плана** блога (см. PLAN.md §20)

### 12.3. Анализ Web Vitals в полях ⬜
- Через 28 дней появятся реальные CWV данные в Search Console
- Если LCP > 2.5s или CLS > 0.1 на >25% страниц — оптимизировать (см.
  `docs/blog/PLAN.md` §15 для целей по CWV)

### 12.4. Метрика Webvisor — где бросают чтение ⬜
- Y.Metrika → Веб-визор → отфильтровать `/blog/*` страницы
- Просмотреть 5-10 сессий — увидеть где scrolling stop, где скучно
- Использовать как input для редактуры существующих статей

### 12.5. Резервная копия БД ⬜
- Через 1 неделю работы — убедиться что Spatie Backup делает копии
- Скачать одну копию локально, попробовать восстановить в тестовую БД
- **Восстановление не проверенное = бэкапа не существует**

---

## 13. Что НЕ нужно делать (антипаттерны)

- ❌ **НЕ покупать ссылки** для буста SEO — Google и Yandex наказывают манипулятивные ссылки. Получать ссылки **естественно** через PR/гостевые посты в отраслевых журналах
- ❌ **НЕ накручивать поведенческие сигналы** через ботов — Yandex автоматически детектит и понижает в выдаче (АГС-фильтр)
- ❌ **НЕ создавать дубликаты страниц** под разные ключевики — Google склеит и не будет ранжировать ни одну
- ❌ **НЕ ставить fake-author** на статьи (см. `docs/blog/PLAN.md` §3.1)
- ❌ **НЕ отключать noindex в dev-окружении** — если когда-то понадобится повторно поднять dev.triad.kz для тестов, сначала проверь что noindex active
- ❌ **НЕ публиковать тестовые статьи через live admin** — она попадёт в sitemap → IndexNow → Yandex. Использовать draft (`published_at = null`) или preview link (§ Filament action «Превью на сайте»)

---

## 14. Ссылки на документацию

- [docs/blog/PLAN.md](./blog/PLAN.md) — стратегический SEO-план блога
- [docs/blog/CLAUDE.md](./blog/CLAUDE.md) — engineering conventions блога
- [_legacy/план_работ_triad.md](../_legacy/план_работ_triad.md) — исходный план миграции с WP
- Yandex.Webmaster docs: https://yandex.com/support/webmaster/
- Google Search Console docs: https://support.google.com/webmasters/
- IndexNow docs: https://www.indexnow.org/documentation
- Spatie Backup docs: https://spatie.be/docs/laravel-backup

---

## 15. Контакты ответственных

Заполнить заказчиком:

| Роль | ФИО | Контакт | Доступы |
|---|---|---|---|
| Владелец Google аккаунта | | | GSC + GA4 + GBP |
| Владелец Yandex аккаунта | | | Webmaster + Metrika + Бизнес |
| Plesk admin | | | hosting panel |
| DNS admin | | | NS / TXT записи |
| Контент-маркетолог | | | админка → блог + Wordstat |
| Юрист (для legal pages) | | | проверка privacy/terms |

---

## Журнал выполнения

| Дата | Что сделано | Кто | Заметки |
|---|---|---|---|
| | | | |
| | | | |
| | | | |
