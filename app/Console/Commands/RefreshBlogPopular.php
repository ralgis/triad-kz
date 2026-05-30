<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\YandexMetrikaPopular;
use Illuminate\Console\Command;

/**
 * Refreshes the «Популярные статьи» cache from Yandex Metrika Reports
 * API. Scheduled via Plesk Cron every 4h (matches CACHE_TTL_SECONDS in
 * YandexMetrikaPopular).
 *
 * Usage: `php artisan blog:refresh-popular`
 */
final class RefreshBlogPopular extends Command
{
    protected $signature = 'blog:refresh-popular {--limit=10 : How many top URLs to fetch}';

    protected $description = 'Pull top-N popular blog article slugs from Yandex Metrika into cache.';

    public function handle(): int
    {
        $service = new YandexMetrikaPopular(Setting::current());

        $limit = (int) $this->option('limit');
        $slugs = $service->refresh($limit > 0 ? $limit : 10);

        if ($slugs === []) {
            $this->warn('No slugs returned — check counter ID / OAuth token / Metrika API quota.');

            return self::FAILURE;
        }

        $this->info(sprintf('Cached %d popular slugs.', count($slugs)));
        foreach ($slugs as $i => $slug) {
            $this->line(sprintf('  %2d. /blog/%s', $i + 1, $slug));
        }

        return self::SUCCESS;
    }
}
