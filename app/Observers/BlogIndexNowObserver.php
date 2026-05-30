<?php

declare(strict_types=1);

namespace App\Observers;

use App\Contracts\HasPublicUrl;
use App\Models\Article;
use App\Models\Setting;
use App\Services\IndexNowClient;
use Illuminate\Database\Eloquent\Model;

/**
 * Pings IndexNow whenever a published Article or any BlogCategory
 * gains/changes/loses a URL the search engines might index.
 *
 * Rules:
 * - Article: ping only when it's actually visible (scopePublished is the
 *   truth). A draft save shouldn't tell Yandex anything.
 * - On delete: ping the now-gone URL so engines refresh their cache and
 *   don't keep returning a 404-or-410.
 * - We swallow failures (see IndexNowClient) — this is best-effort
 *   signalling, not a critical path.
 *
 * Attach via Article::observe() + BlogCategory::observe() in a service
 * provider (lands in AppServiceProvider::boot() — see commit).
 */
final class BlogIndexNowObserver
{
    /**
     * @param Model&HasPublicUrl $model
     */
    public function saved(Model $model): void
    {
        if ($model instanceof Article && ! $this->isVisibleArticle($model)) {
            return;
        }

        $this->client()->submit($model->url());
    }

    /**
     * @param Model&HasPublicUrl $model
     */
    public function deleted(Model $model): void
    {
        $this->client()->submit($model->url());
    }

    private function isVisibleArticle(Article $article): bool
    {
        /** @var \Illuminate\Support\Carbon|null $when */
        $when = $article->published_at;

        return $when !== null && $when->lte(now());
    }

    private function client(): IndexNowClient
    {
        return new IndexNowClient(Setting::current());
    }
}
