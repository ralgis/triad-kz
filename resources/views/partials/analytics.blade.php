{{--
    Public-site analytics partial. Renders Яндекс.Метрика и Google
    GA4 (gtag.js) трекеры из ID, заполненных админом в Settings.

    Env-gated to `production` — dev.triad.kz traffic (наши тесты)
    не должно засорять prod-отчёты. Без этого counter ID 38595400
    получит хиты с каждого нашего клика на /admin/settings, а в
    отчётах будет смесь real-юзеров и нас.

    Я.Метрика поддерживает любой counter ID (число). Google поле
    рендерится только для GA4-формата (G-XXXXXXXXXX). Старый UA-…
    с 2023-07-01 отключен Google'ом — даже если админ впишет, мы
    не рендерим: trash-скрипт пуляющий 410 Gone из gtag.js никому
    не нужен.

    NB: внешние <script src=…> здесь без `integrity` / SRI хешей.
    Это осознанно — Я.Метрика и Google публикуют tag.js / gtag.js
    как live-сервисы, не как иммутабельные библиотеки, и регулярно
    обновляют их без публикации новых хешей. Пинить хеш = поломать
    сбор статистики при первом же апдейте. Trust model — TLS до
    `mc.yandex.ru` и `googletagmanager.com`, не иммутабельность
    конкретного бандла. SRI имеет смысл для pinned-jQuery с
    cdnjs, не для официальных трекеров.
--}}
@php
    $settings ??= \App\Models\Setting::current();
    $yandexId = trim((string) ($settings->analytics_yandex_id ?? ''));
    $googleId = trim((string) ($settings->analytics_google_id ?? ''));
    $isProd = app()->environment('production');
@endphp

@if($isProd && $yandexId !== '' && ctype_digit($yandexId))
    {{-- Яндекс.Метрика — counter snippet, copy-paste-стандарт от Я.Метрики --}}
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

        ym({{ $yandexId }}, "init", {
            clickmap: true,
            trackLinks: true,
            accurateTrackBounce: true,
            webvisor: true
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/{{ $yandexId }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
@endif

@if($isProd && str_starts_with($googleId, 'G-'))
    {{-- Google Analytics 4 — gtag.js, only when ID is in GA4 format --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $googleId }}');
    </script>
@endif
