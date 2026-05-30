<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IndexNow client — POSTs URLs to api.indexnow.org so Yandex + Bing
 * pick up changes within ~15 minutes instead of waiting for natural
 * crawl (2-14 days). Google does NOT support IndexNow — for Google
 * we rely on Search Console + natural crawl.
 *
 * Spec: https://www.indexnow.org/documentation
 *
 * Key file lives at public/{key}.txt — the search engine fetches it
 * to verify the caller owns the domain. Key is generated once and
 * stored in settings.indexnow_key (admin can rotate via Settings).
 *
 * On any error (network, 4xx, 5xx) we LOG and swallow — IndexNow
 * is best-effort signaling, not a critical pipeline step.
 */
final class IndexNowClient
{
    private const ENDPOINT = 'https://api.indexnow.org/IndexNow';

    public function __construct(
        private readonly Setting $settings,
    ) {}

    public function isConfigured(): bool
    {
        return ! empty($this->settings->indexnow_key);
    }

    /**
     * Submit a single URL. Returns true on 200/202, false otherwise
     * (including unconfigured / network errors).
     */
    public function submit(string $url): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(5)->post(self::ENDPOINT, [
                'host' => parse_url($url, PHP_URL_HOST),
                'key' => $this->settings->indexnow_key,
                'urlList' => [$url],
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('IndexNow submission failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('IndexNow submission exception', [
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Submit a batch. IndexNow accepts up to 10,000 URLs per request,
     * but realistically we'll never batch more than a few — articles
     * and rubrics change one at a time.
     *
     * @param list<string> $urls
     */
    public function submitBatch(array $urls): bool
    {
        if (! $this->isConfigured() || $urls === []) {
            return false;
        }

        try {
            $response = Http::timeout(10)->post(self::ENDPOINT, [
                'host' => parse_url($urls[0], PHP_URL_HOST),
                'key' => $this->settings->indexnow_key,
                'urlList' => $urls,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('IndexNow batch submission failed', [
                'count' => count($urls),
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('IndexNow batch exception', [
                'count' => count($urls),
                'exception' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
