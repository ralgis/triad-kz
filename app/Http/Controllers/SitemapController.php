<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dynamic /sitemap.xml driven by what's published in the DB. Built on
 * Spatie's Sitemap renderer, but we hand-curate the URL list rather
 * than rely on its auto-crawler: published gating is model-specific
 * and a crawler that walks dev with basic auth in front would 401 on
 * every link.
 *
 * Priorities and lastmod follow generic SEO heuristics:
 *   - home / catalog                : 1.0 / 0.9 (entry points)
 *   - category & product detail     : 0.8 / 0.7 (depth-based)
 *   - articles & static pages       : 0.6
 *   - lastmod from updated_at where present, otherwise null (omitted).
 *
 * /cart, /checkout, /contacts-form, /order/* are deliberately omitted —
 * they're noindex on every response (see EnsureNoindexInNonProd in
 * non-prod, and in-page <meta> on every commerce/order page).
 */
final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = Sitemap::create()
            ->add(Url::create(url('/'))->setPriority(1.0))
            ->add(Url::create(url('/catalog'))->setPriority(0.9))
            ->add(Url::create(url('/blog'))->setPriority(0.7))
            ->add(Url::create(url('/contacts'))->setPriority(0.6));

        // `listed=false` rows stay reachable on direct URL but are
        // intentionally excluded from the sitemap — admin uses the
        // flag exactly to keep these out of search engines' fresh
        // crawl set without breaking inbound links.
        Category::query()
            ->where('published', true)
            ->where('listed', true)
            ->get()
            ->each(fn (Category $c) => $sitemap->add(
                Url::create($c->url())
                    ->setLastModificationDate($c->updated_at ?? now())
                    ->setPriority(0.8),
            ));

        Product::query()
            ->published()
            ->listed()
            ->with('categories:id,slug')
            ->get()
            ->each(fn (Product $p) => $sitemap->add(
                Url::create($p->url())
                    ->setLastModificationDate($p->updated_at ?? now())
                    ->setPriority(0.7),
            ));

        Article::query()
            ->published()
            ->get()
            ->each(fn (Article $a) => $sitemap->add(
                Url::create($a->url())
                    ->setLastModificationDate($a->updated_at ?? $a->published_at ?? now())
                    ->setPriority(0.6),
            ));

        // Static Pages — but skip /contacts (it has a dedicated route
        // already added above) so we don't list the same URL twice.
        Page::query()
            ->where('slug', '!=', 'contacts')
            ->get()
            ->each(fn (Page $page) => $sitemap->add(
                Url::create(url('/'.$page->slug))
                    ->setLastModificationDate($page->updated_at ?? now())
                    ->setPriority(0.6),
            ));

        return $sitemap->toResponse(request());
    }
}
