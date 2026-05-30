<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pulls article-pageview counts from the Yandex Metrika Reports API
 * (also called Stat API — see [docs](https://yandex.com/dev/metrika/en/stat/)).
 * Avoids the DB-counter pattern (see PLAN.md §16.3 — race conditions +
 * BD load + cookie-debounce is bypassable).
 *
 * Caches results in Laravel cache for 4h — Metrika data updates ~every
 * 2 hours, more frequent polling wastes API quota (5000 req/day default).
 *
 * Pre-requisites for the live path:
 * - settings.analytics_yandex_id populated (the counter ID)
 * - settings.yandex_metrika_oauth_token populated (Phase 3 admin form)
 * - Counter has «Permission to access stats» granted to the OAuth app
 *
 * When token is missing or API returns error, we LOG and return an
 * empty array. Caller (BlogController::index) gracefully degrades to
 * the chronological list — popular sidebar just stays hidden.
 *
 * Refresh via: `php artisan blog:refresh-popular`
 */
final class YandexMetrikaPopular
{
    private const CACHE_KEY = 'blog.popular.slugs';

    private const CACHE_TTL_SECONDS = 14_400; // 4 hours

    public function __construct(
        private readonly Setting $settings,
    ) {}

    /**
     * Currently-cached popular slugs (top-N article slugs by pageviews).
     * Returns null when cache is cold and we haven't successfully fetched
     * yet — caller decides whether to skip the popular block or trigger
     * refresh inline.
     *
     * @return list<string>|null
     */
    public function cachedSlugs(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        return is_array($cached) ? array_values($cached) : null;
    }

    /**
     * Fetch fresh, cache, and return. Called from
     * `articles:refresh-popular` cron command.
     *
     * @return list<string>
     */
    public function refresh(int $limit = 10): array
    {
        $counter = $this->settings->analytics_yandex_id;
        $token = $this->settings->yandex_metrika_oauth_token ?? null;

        if (! $counter || ! $token) {
            Log::info('Yandex Metrika popular skipped: counter ID or token missing.');

            return [];
        }

        try {
            $response = Http::withToken($token, 'OAuth')
                ->timeout(10)
                ->get('https://api-metrika.yandex.net/stat/v1/data', [
                    'ids' => (string) $counter,
                    'metrics' => 'ym:pv:pageviews',
                    'dimensions' => 'ym:pv:URLPath',
                    'filters' => "ym:pv:URLPath=~'^/blog/[^/]+$'",
                    'sort' => '-ym:pv:pageviews',
                    'limit' => $limit,
                    'date1' => '7daysAgo',
                    'date2' => 'today',
                ]);

            if (! $response->successful()) {
                Log::warning('Yandex Metrika Reports API non-2xx', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return [];
            }

            $rows = $response->json('data') ?? [];
            $slugs = [];
            foreach ($rows as $row) {
                $path = $row['dimensions'][0]['name'] ?? null;
                if (is_string($path) && preg_match('#^/blog/([a-z0-9-]+)$#', $path, $m)) {
                    $slugs[] = $m[1];
                }
            }

            Cache::put(self::CACHE_KEY, $slugs, self::CACHE_TTL_SECONDS);

            return $slugs;
        } catch (\Throwable $e) {
            Log::warning('Yandex Metrika Reports API exception', [
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
