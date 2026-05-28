<?php

declare(strict_types=1);

namespace App\Observers;

use App\Contracts\HasPublicUrl;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;

/**
 * Auto-creates a 301 redirect from the OLD path to the NEW path whenever
 * a slug changes on Category/Product/Article/Page.
 *
 * Works on any model that:
 *   1) has a `slug` column;
 *   2) exposes `url(): string` returning the public-facing URL.
 *
 * Attached via the HasSlugRedirect trait on each model.
 *
 * Edge cases:
 *   - First-time save (no original slug): nothing to redirect from.
 *   - Slug rename back to a previously-used path: we upsert by `from`,
 *     so we don't insert duplicate rows.
 *   - Product slug change: only the direct path moves. If the parent
 *     Category slug changes, that's a separate event covered by
 *     CategoryObserver — we leave product-paths-under-renamed-categories
 *     as a Phase-4 cutover concern (covered by bulk-imported CSV map).
 */
final class SlugRedirectObserver
{
    /**
     * Single instance reused by HasSlugRedirect's static callbacks across
     * the four content models. Lets us thread state from updating() to
     * updated() via $originalUrls — separate callback invocations would
     * otherwise hit separate instances.
     */
    private static ?self $instance = null;

    public static function shared(): self
    {
        return self::$instance ??= new self;
    }

    /**
     * Remember the original URL before any field is touched, so we can
     * compare in updated() once Eloquent has applied the new values.
     *
     * Keyed by Model::class.':'.id so it survives multiple concurrent
     * model updates in the same request (e.g. bulk admin action).
     *
     * @var array<string, string>
     */
    private array $originalUrls = [];

    /**
     * @param Model&HasPublicUrl $model
     */
    public function updating(Model $model): void
    {
        if (! $this->slugChanged($model)) {
            return;
        }

        $originalSlug = (string) $model->getOriginal('slug');
        if ($originalSlug === '') {
            return;
        }

        // Build the URL the model HAD before the slug change, by cloning
        // the model and reverting just the slug. Cleaner than parsing the
        // current url() — different models compose paths differently.
        $cloneForOldUrl = clone $model;
        $cloneForOldUrl->slug = $originalSlug;
        $oldPath = self::toPath($cloneForOldUrl->url());

        $this->originalUrls[self::stash($model)] = $oldPath;
    }

    /**
     * @param Model&HasPublicUrl $model
     */
    public function updated(Model $model): void
    {
        $key = self::stash($model);
        $oldPath = $this->originalUrls[$key] ?? null;
        if ($oldPath === null) {
            return;
        }

        unset($this->originalUrls[$key]);

        $newPath = self::toPath($model->url());
        if ($oldPath === '' || $oldPath === $newPath) {
            return;
        }

        // Upsert by `from` — avoids unique-constraint collisions when a
        // path is renamed twice or back-and-forth.
        Redirect::updateOrCreate(
            ['from' => $oldPath],
            ['to' => $newPath, 'status' => 301],
        );
    }

    private function slugChanged(Model $model): bool
    {
        if (! $model->isDirty('slug')) {
            return false;
        }
        $original = $model->getOriginal('slug');
        $current = $model->getAttribute('slug');

        return $original !== null && $original !== '' && $original !== $current;
    }

    private static function stash(Model $model): string
    {
        return $model::class.':'.($model->getKey() ?? spl_object_id($model));
    }

    /**
     * Strip the scheme/host off a full URL, leaving only `/path/` for
     * matching against incoming request paths.
     */
    private static function toPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }
}
