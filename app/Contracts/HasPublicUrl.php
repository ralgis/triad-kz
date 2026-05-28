<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Implemented by content models (Category/Product/Article/Page) that
 * expose a public-facing URL. Used by SlugRedirectObserver to compute
 * old/new paths during slug changes — see app/Observers/SlugRedirectObserver.php.
 */
interface HasPublicUrl
{
    public function url(): string;
}
