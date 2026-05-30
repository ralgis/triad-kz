<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Article;
use App\Models\BlogCategory;
use App\Observers\BlogIndexNowObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimits();
        $this->configureBlogObservers();
    }

    /**
     * IndexNow ping for blog content changes. Attaches to Article +
     * BlogCategory lifecycle events. Disabled when no key configured
     * (the observer's client gates on isConfigured()).
     */
    private function configureBlogObservers(): void
    {
        Article::observe(BlogIndexNowObserver::class);
        BlogCategory::observe(BlogIndexNowObserver::class);
    }

    /**
     * Named rate-limit buckets, applied via `throttle:<name>` on routes.
     *
     * Per-IP only — we don't want a logged-in admin to share a bucket with
     * a guest customer. Authenticated admin endpoints have their own
     * higher limits if needed.
     */
    private function configureRateLimits(): void
    {
        // POST /checkout/submit/ — buyers are real humans, but bots fishing
        // for cheap PDF generation can hammer this. 5/min is generous for
        // a customer correcting a typo + restubmitting.
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(5)->by((string) $r->ip()));

        // POST /contacts/submit/ + /products/{id}/inquiry/ — the lower the
        // friction the heavier the spam.
        RateLimiter::for('contact', fn (Request $r) => Limit::perMinute(3)->by((string) $r->ip()));

        // POST /admin/login — password-spraying is the realistic threat.
        // Filament default is 5/min; we make it explicit and add IP keying.
        RateLimiter::for('admin-login', fn (Request $r) => Limit::perMinute(5)->by((string) $r->ip()));
    }
}
