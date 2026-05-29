<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/**
 * Per-model-class storage layout. Each content type gets its own
 * top-level folder under `storage/app/public/` so directory listings
 * + nginx access logs read cleanly:
 *
 *   storage/app/public/
 *   ├── goods/{id}/…           ← Product (real, gallery, blueprint)
 *   ├── articles/{id}/…        ← Article (cover)
 *   ├── categories/{id}/…      ← Category (cover)
 *   └── settings/{id}/…        ← Setting (logo, og_default)
 *
 * Anything not in the map falls back to Spatie's flat default
 * `{id}/…` so a future model with media works without touching this
 * file — at the cost of one un-grouped folder until we add a row here.
 */
final class TriadPathGenerator extends DefaultPathGenerator
{
    /**
     * @var array<class-string, string>
     */
    private const FOLDER_BY_MODEL = [
        Product::class => 'goods',
        Article::class => 'articles',
        Category::class => 'categories',
        Setting::class => 'settings',
    ];

    protected function getBasePath(Media $media): string
    {
        $folder = self::FOLDER_BY_MODEL[$media->model_type] ?? null;
        if ($folder !== null) {
            return $folder.'/'.$media->getKey();
        }

        return parent::getBasePath($media);
    }
}
