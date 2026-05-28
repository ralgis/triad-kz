# Cutover Runbook — dev.triad.kz → triad.kz

Last reviewed 2026-05-29. Reflects code as of commit `feded1dc` +
the Phase 5 redirect builder.

This is the live-flip from the legacy WP4.3 install on `triad.kz` to
our Laravel rewrite that currently lives on `dev.triad.kz`. Done in
one Plesk session, ~30 minutes of focused work + 7 days of monitoring.

## Before you start

You need:
- Access to the Plesk panel for the triad.kz domain
- The latest WP backup + ZIP from `_legacy/backup/`
- The Plesk root MariaDB credentials for the `triadkz_dev` DB
- Google Search Console + Yandex Webmaster verified for triad.kz
  (both via meta tag method — keep the verification meta tags in
  Settings → analytics fields, the head partial reads them)

You do NOT need:
- SSH (Plesk Laravel Toolkit handles every artisan call we make)
- A second domain — DNS doesn't move; only the document root rename

## Phase 0 — Pre-flight (T-2 days)

Run on dev.triad.kz BEFORE touching anything else.

- [ ] Laravel Toolkit → Artisan: `migrate:status` shows no pending.
- [ ] Lighthouse on `/`, `/catalog`, a product detail, `/blog/X` —
      Performance ≥85 mobile, ≥90 desktop, SEO=100, A11y ≥90.
- [ ] Manual end-to-end checkout: add 2 products to cart → checkout
      as индивидуал (cash) → confirm 200 on `/order/T-NNNNNN`.
- [ ] Manual end-to-end checkout: ditto as юрлицо (bank transfer) →
      confirm PDF download works on the success page.
- [ ] Contact form: submit on `/contacts` → confirm admin notification
      lands at Settings.email_recipient.
- [ ] 301 sanity: hit 10 random URLs from
      `triad:build-redirects --dry-run` → each 301s to the new path.
- [ ] `curl -sI https://dev.triad.kz/sitemap.xml` → 200, contents
      include each published Category, Product, Article, Page.
- [ ] `curl -sI https://dev.triad.kz/robots.txt` → still
      `Disallow: /` (no leaked prod robots).
- [ ] `composer audit` from local — no known CVEs in dependencies.
- [ ] Plesk: download a full backup of the LIVE triad.kz install
      (just in case). Name it `pre-cutover-2026-XX-XX.tar.gz`.

## Phase 1 — Content + redirect map (T-1 day)

We've already imported content into dev via Phase 4. This step locks
the 301 map and verifies the prod-shape data is final.

- [ ] Spin up the wp_legacy MariaDB helper inside the prod box
      (Plesk → MariaDB → new DB `wp_legacy`, import the WP dump
      via phpMyAdmin Plesk extension). Alternative: do this locally
      and skip — content is already in dev DB, redirects can be
      built once locally and SQL-dumped onto prod.
- [ ] Toolkit Artisan: `triad:build-redirects --dry-run` →
      eyeball the output. Should show ~50 rows. Anything that looks
      wrong → fix STATIC_MAP / CATEGORY_BY_WP_ID in the command
      and re-deploy.
- [ ] Toolkit Artisan: `triad:build-redirects` → writes 49+ rows.
- [ ] Filament admin: Settings → fill in everything that's still empty
      (phone, public_email, email_recipient, address, company_*).
      Live data, no placeholders.
- [ ] Filament admin: open every imported Product → review price →
      flip `price_visible=true` on the ones you want public. Anything
      left hidden → admin manually replies to «Запросить цену» leads.
- [ ] Filament admin: every Category — upload a cover image
      (Spatie `cover` collection). Without it, the homepage and
      catalog grid show a giant first-letter placeholder.
- [ ] Optional: write 1 article in Filament for the "статьи" tab so
      `/blog` isn't empty on launch day.

## Phase 2 — DNS / domain swap (cutover hour)

Plesk doesn't move DNS during a domain rename; it just rewires the
document root. So this is a fast operation with no DNS propagation
wait.

- [ ] Plesk → Subscriptions → triad.kz subscription → flip
      maintenance mode on for the legacy site (so it doesn't take
      orders during the flip — there aren't any anyway, but be
      defensive).
- [ ] Plesk → Domain settings → triad.kz → rename to
      `archive.triad.kz` (or `legacy.triad.kz` — anything that
      moves it off the canonical name).
- [ ] Plesk → Domain settings → dev.triad.kz → rename to
      `triad.kz`. Plesk repoints the doc root and SSL cert.
- [ ] Plesk → Защищённые папки on the new triad.kz → REMOVE basic
      auth. (Don't forget — site is now 401-walled for real users
      until you do.)
- [ ] Plesk File Manager → `/var/www/vhosts/triad.kz/.../.env` →
      flip `APP_ENV=production`, `APP_DEBUG=false`, set
      `APP_URL=https://triad.kz`.
- [ ] Toolkit Artisan: `config:clear && view:clear && route:clear
      && cache:clear && filament:cache-components`.
- [ ] `curl -I https://triad.kz/robots.txt` →
      `Disallow:` (empty, allow-all). If still `Disallow: /` →
      APP_ENV didn't reload, repeat the clear above.
- [ ] `curl -I https://triad.kz/` → 200, NO `X-Robots-Tag: noindex`
      header. If still noindex, same fix.

## Phase 3 — Re-verify live (cutover hour + 30 min)

- [ ] Hit each in a real browser:
      `/`, `/catalog`, `/catalog/fbs`, a product, `/blog`, `/about`,
      `/contacts`. All 200, all rendering content correctly.
- [ ] Hit 5 OLD URLs from the redirect map in a real browser:
      each gives a single 301 hop to the correct new URL.
- [ ] Run Lighthouse on `/` from PageSpeed Insights (mobile +
      desktop). Numbers should match dev within ±5pts.
- [ ] Google Rich Results Test on `/` (Organization),
      a product page (Product + BreadcrumbList), an article
      (Article). All three pass without errors.
- [ ] Submit `https://triad.kz/sitemap.xml` to:
      - GSC → Sitemaps → Add sitemap
      - Я.Вебмастер → Индексирование → Файлы Sitemap
- [ ] Open the Plesk error log and the Laravel log — both empty for
      the last 30 minutes. Anything red → investigate before
      announcing.

## Phase 4 — Monitor (cutover + 7 days)

Daily at the same time of day:

- [ ] GSC → Покрытие → Ошибки. Any new 404? Add a Redirect row.
- [ ] Я.Вебмастер → Индексирование → Страницы в поиске → Excluded.
      Same drill.
- [ ] Filament admin → Redirects → sort by `last_hit_at desc`. New
      hits on rows you wrote at cutover → good, 301 is working. New
      hits on the catch-all 404 → missing from the map; add it.
- [ ] Plesk Resource Usage chart — CPU stays under ~30% on
      checkout/contact form submissions. Anything sustained higher
      → investigate (likely a runaway query, NOT load: this site
      gets tens of visitors/day).
- [ ] After day 7, run `php artisan triad:build-redirects` ONE more
      time from a fresh GSC export of indexed URLs (paste old URLs
      into STATIC_MAP first). Catches anything Google indexed that
      our auto-builder missed.

## Rollback (if cutover goes sideways)

Within the first hour, before traffic settles:

- [ ] Plesk → rename triad.kz → dev.triad.kz, archive.triad.kz →
      triad.kz. (Inverse of Phase 2.)
- [ ] Restore basic auth on dev (now archive → dev).
- [ ] Investigate the issue on dev with no public exposure.

After day 1 (DNS / search results may have settled): rollback is
costly, fix forward instead.

## Useful commands reference

All run via Plesk Laravel Toolkit → Artisan tab:

```
# Status / sanity
migrate:status
route:list
about

# Cache reset after env change
config:clear && view:clear && route:clear && cache:clear

# Filament specifically — Resource discovery cache
filament:cache-components

# Force-clear maintenance
up

# Redirect builder (re-run any time)
triad:build-redirects --dry-run
triad:build-redirects
```

## After cutover, kill the helper

The wp_legacy MariaDB on prod was only needed for the redirect
builder + content import. Drop it from Plesk → DBs once the 7-day
window passes without surprises. Keeps the attack surface small
and the backup snapshots lean.
