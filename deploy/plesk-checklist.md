# Plesk deploy checklist — dev.triad.kz

> One-time setup. Идёшь по пунктам сверху вниз. После завершения — деплой
> на любой git push в main триггерит post-deploy hook автоматически.

## Что уже сделано ✅

- Хостинг и DNS → Корневая папка = `dev.triad.kz/public`
- SSL/TLS включён + HTTP→HTTPS 301
- Let's Encrypt сертификат выпущен
- Системный пользователь: `triadkz`

---

## 1. PHP — проверить версию + extensions

**Plesk → Domains → dev.triad.kz → PHP Settings**

- **PHP version:** должна быть **8.4.x** (видели 8.4.21 ранее)
- **Required extensions** — должны быть включены (галки):
  - `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`
  - `gd` (для media-library image processing)
  - `intl` (для Filament Section / formatters)
  - `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`
  - `mysqli`, `tokenizer`, `xml`, `zip`
  - `exif` (для media-library)
- Если **gd** или **intl** отключены — попроси ps-cloud-services включить
  (Plesk Free тариф может ограничивать)

## 2. База данных — MariaDB

**Plesk → Domains → dev.triad.kz → Databases → Add Database**

- **Database name:** `triadkz_dev` (или подобное — Plesk может префиксить
  именем подписки, типа `triadkz_triadkzdev`)
- **Database user:** создать нового, не root. Например `triadkz_dev`
- **Password:** Generate (сложный) — **СКОПИРУЙ И СОХРАНИ**

После создания — **запиши себе четыре значения** (понадобятся для `.env`):
```
DB_HOST=localhost
DB_DATABASE=<имя_созданной_базы>
DB_USERNAME=<созданный_user>
DB_PASSWORD=<сгенерированный_пароль>
```

## 3. Git — подключение к GitHub

**Plesk → Domains → dev.triad.kz → Git → Add Repository**

| Поле | Значение |
|---|---|
| Repository URL | `https://github.com/ralgis/triad-kz.git` |
| Branch | `main` |
| Deployment target | (по умолчанию = `httpdocs` → `/var/www/vhosts/triad.kz/dev.triad.kz/`) |
| Deployment mode | **Automatic** (deploy on every push) — или **Manual** для контроля |

**После клонирования** репо — кнопка **Settings** на репозитории:

### Additional deployment actions

Вставить (полностью):

```bash
# Run post-deploy hook from the deploy/ directory
bash /var/www/vhosts/triad.kz/dev.triad.kz/deploy/post-deploy.sh
```

## 4. .env на сервере

**Plesk → Domains → dev.triad.kz → File Manager**

Перейти в корень `dev.triad.kz/` (НЕ `dev.triad.kz/public/`!).

Создать файл `.env` рядом с `artisan`, `composer.json`. Скопировать содержимое
из `.env.example` (он в репо). Изменить эти значения:

```env
APP_NAME="ТРИ АД Construction"
APP_ENV=staging
APP_KEY=                          # ← заполнишь ниже шагом 6
APP_DEBUG=true                    # на staging оставляем true, на prod ставим false
APP_URL=https://dev.triad.kz

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<из шага 2>
DB_USERNAME=<из шага 2>
DB_PASSWORD=<из шага 2>

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=25
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@triad.kz"
MAIL_FROM_NAME="ТРИ АД Construction"

TRIAD_INQUIRY_EMAIL=ravacom@mail.ru

SESSION_SECURE_COOKIE=true        # HTTPS-only cookies, мы за Let's Encrypt
```

Сохранить.

## 5. Первый deploy — нажать **Pull Updates** в Plesk Git

**Plesk → Domains → dev.triad.kz → Git → твой репо → Pull Updates**

Что произойдёт:
1. Plesk клонирует код в `dev.triad.kz/` (не в `dev.triad.kz/public/` — в
   родительскую папку)
2. Запустит `bash deploy/post-deploy.sh` который сделает:
   - `composer install --no-dev`
   - `php artisan migrate --force` (создаст таблицы в MariaDB)
   - `php artisan storage:link`
   - cache rebuild

**В Plesk будут логи** — смотри их, если что-то падает (обычно — отсутствует
PHP-extension или DB-creds неверные).

## 6. Сгенерировать APP_KEY (один раз)

Если в `.env` оставил `APP_KEY=` пустым — Laravel будет ругаться. Два варианта:

**Вариант А — через Plesk Scheduled Tasks**:
1. Plesk → Domains → dev.triad.kz → Scheduled Tasks → Add Task
2. Тип: Run a command
3. Command: `/opt/plesk/php/8.4/bin/php /var/www/vhosts/triad.kz/dev.triad.kz/artisan key:generate --force --show`
4. Run as: `Run task` (один раз)
5. **Скопируй output** — он покажет ключ типа `base64:abc...`
6. Вставь его в `.env` руками: `APP_KEY=base64:abc...`
7. Удали задачу

**Вариант Б (проще) — локально**:
1. На localhost запусти `wsl --cd /home/ralgis/triad-kz -- php artisan key:generate --show`
2. Вставь output в server `.env`

## 7. Создать admin-пользователя

**Plesk → Scheduled Tasks → Add Task → Run-once:**

```
/opt/plesk/php/8.4/bin/php /var/www/vhosts/triad.kz/dev.triad.kz/artisan triad:create-admin admin@triad.kz <твой_пароль_8+_символов> Admin
```

Команда idempotent — можно re-run если забыл пароль (создаст/обновит).

## 8. HTTP Basic Auth — закрыть staging от индексации

**Plesk → Domains → dev.triad.kz → Защищённые папки → Add Protected Directory**

- Directory path: `/` (весь сайт)
- Header / Realm: «Triad-KZ Staging»
- Add user: `staging` / сложный пароль

Это закрывает доступ паролем + поисковики не получат страницы.

**Альтернатива** — заранее у нас в коде есть `EnsureNoindexInNonProd`
middleware (если `APP_ENV != production` отдаёт `X-Robots-Tag: noindex`),
но HTTP basic auth надёжнее.

## 9. Smoke test

После всех шагов выше — открой в браузере:

| URL | Ожидание |
|---|---|
| `https://dev.triad.kz/` | (после basic-auth) → Laravel welcome или 500 если что-то падает |
| `https://dev.triad.kz/admin/login` | Filament login page |

Если 500 — проверь:
- `https://dev.triad.kz/admin/login` → лог в Plesk → Logs → error_log
- `/var/www/vhosts/triad.kz/dev.triad.kz/storage/logs/laravel.log` через File Manager

## 10. Что дальше после успешного deploy

- Я допишу content-import команду чтобы заполнить каталог ЖБИ из старого WP
- Phase 1.2.b — остальные admin resources (Settings, Menu, Order workflow и т.д.)
- Phase 2 — frontend на Tailwind v4

## Что НЕ нужно делать

- Не давай миру вход без HTTP basic auth (пункт 8 обязателен на dev)
- Не выкладывай `.env` в git — он gitignored
- Не правь файлы в `dev.triad.kz/public/` напрямую через File Manager — они
  перезатрутся следующим Git Pull. Все правки → через локалку → push → deploy
- Не запускай `migrate:fresh` на prod — `migrate:fresh` дропает таблицы!
  В post-deploy hook стоит `migrate --force` (только новые миграции)

## Troubleshooting

| Ошибка | Причина | Фикс |
|---|---|---|
| `Class "Imagick" not found` | imagick extension off | Plesk PHP Settings → enable |
| `SQLSTATE[HY000]: Unknown database` | DB creds в `.env` неверные | См. п.4 |
| `Permission denied` для `storage/` | chmod после deploy | Plesk → File Manager → `chmod -R 775 storage bootstrap/cache` (через GUI) |
| `Mix manifest does not exist` | npm assets не закоммичены | На локали `npm run build` + push |
| `419 Page Expired` (CSRF) | secure cookies + HTTP | `SESSION_SECURE_COOKIE=false` — но у тебя есть SSL, оставь true |
