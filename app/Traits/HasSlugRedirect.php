<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\HasPublicUrl;
use App\Observers\SlugRedirectObserver;
use Illuminate\Database\Eloquent\Model;

/**
 * Attaches the SlugRedirectObserver to the model's lifecycle so that any
 * slug change auto-creates a 301 Redirect row.
 *
 * Use on models that have a `slug` column AND a public `url(): string`
 * method. See Category/Product/Article/Page.
 *
 * We register the callbacks directly instead of `static::observe()` —
 * the latter triggers a recursive `Model::bootIfNotBooted` on the same
 * class because it instantiates the observer through the container
 * mid-boot.
 *
 * @phpstan-require-extends Model
 *
 * @phpstan-require-implements HasPublicUrl
 */
trait HasSlugRedirect
{
    public static function bootHasSlugRedirect(): void
    {
        static::updating(static function (Model $model): void {
            assert($model instanceof HasPublicUrl);
            SlugRedirectObserver::shared()->updating($model);
        });

        static::updated(static function (Model $model): void {
            assert($model instanceof HasPublicUrl);
            SlugRedirectObserver::shared()->updated($model);
        });
    }
}
