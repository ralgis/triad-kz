<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AllowDraftPreviewForAdmin;
use App\Models\Article;
use App\Models\BlogCategory;
use App\Models\Setting;
use App\Services\ContentToc;
use App\Services\YandexMetrikaPopular;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BlogController extends Controller
{
    public function index(): View
    {
        // Listing ordering: featured first (sticky to /blog hero strip),
        // then chronological. Pinned sorting only applies in the category
        // view — on the global index, featured does the «promote» job.
        $articles = Article::query()
            ->published()
            ->with('blogCategory')
            ->orderByDesc('featured')
            ->latest('published_at')
            ->paginate(12);

        $featured = Article::query()
            ->published()
            ->where('featured', true)
            ->with('blogCategory')
            ->latest('published_at')
            ->limit(6)
            ->get();

        $categories = BlogCategory::query()
            ->published()
            ->listed()
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderBy('order')
            ->get();

        // Popular sidebar — driven by Yandex Metrika Reports API cache.
        // Cold cache or unconfigured token → empty list → block hidden.
        $popularSlugs = (new YandexMetrikaPopular(Setting::current()))->cachedSlugs() ?? [];
        $popular = $popularSlugs === []
            ? collect()
            : Article::query()
                ->published()
                ->whereIn('slug', $popularSlugs)
                ->get()
                ->sortBy(fn (Article $a) => array_search($a->slug, $popularSlugs, true))
                ->values();

        return view('blog.index', compact('articles', 'featured', 'categories', 'popular'));
    }

    /**
     * Article detail. Fans out into ContentToc + related-block + breadcrumb
     * trail (including the rubric when present). The exists() re-check
     * through scopePublished is the single source of truth for "visible";
     * router model binding alone doesn't enforce the future-date scope.
     */
    public function show(Article $article, ContentToc $toc, Request $request): View
    {
        // Admin-signed ?preview=1 URLs (15 min TTL) bypass the published
        // check — used for draft review from Filament's EditArticle
        // header action. Everyone else gets the standard 404 on drafts
        // and future-scheduled posts.
        $isPreview = AllowDraftPreviewForAdmin::isPreviewing($request);
        if (! $isPreview) {
            abort_unless(
                Article::published()->whereKey($article->id)->exists(),
                404,
            );
        }

        $article->load(['blogCategory', 'pillar', 'products', 'gosts']);

        // TL;DR is admin-typed [summary]...[/summary]. We split it out
        // of the main content so it renders once in the dedicated box
        // and doesn't duplicate inside the article body.
        $tldr = $article->extractTldr();
        $contentBody = $article->contentWithoutTldr();

        $tocItems = $article->toc_enabled ? $toc->extract($contentBody) : [];
        $contentWithAnchors = $toc->injectIds($contentBody);

        $related = $article->relatedInBlogCategory(limit: 4);

        // Pillar-of-cluster: every cluster gets its pillar referenced
        // back. Pillar pages get the list of all their clusters.
        $pillarOfCluster = $article->pillar;
        $clustersOfPillar = $article->is_pillar
            ? $article->clusters()->published()->orderBy('title')->get()
            : collect();

        return view('blog.article', [
            'article' => $article,
            'tldr' => $tldr,
            'tocItems' => $tocItems,
            'contentHtml' => $contentWithAnchors,
            'related' => $related,
            'pillarOfCluster' => $pillarOfCluster,
            'clustersOfPillar' => $clustersOfPillar,
        ]);
    }

    /**
     * Blog-internal search. MVP implementation uses LIKE — fine for the
     * current sub-100-article volume. When the catalog grows past
     * ~500 articles or admin starts complaining about lack of Russian
     * morphology (бетон ≠ бетонный with LIKE), swap the body for
     * Scout-driven Meilisearch index (config in docs/blog/PLAN.md §14).
     *
     * Page is noindex,follow — internal-search URLs are infinite-permutation
     * and shouldn't dilute the index.
     */
    public function search(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $results = $q === ''
            ? collect()
            : Article::query()
                ->published()
                ->where(function ($w) use ($q) {
                    $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                    $w->where('title', 'LIKE', $like)
                        ->orWhere('subtitle', 'LIKE', $like)
                        ->orWhere('excerpt', 'LIKE', $like)
                        ->orWhere('content', 'LIKE', $like);
                })
                ->with('blogCategory')
                ->latest('published_at')
                ->limit(50)
                ->get();

        return view('blog.search', [
            'q' => $q,
            'results' => $results,
        ]);
    }

    public function category(BlogCategory $category): View
    {
        abort_unless($category->published, 404);

        // Ordering within a rubric: currently-pinned first (pinned_until
        // > now), then featured, then chronological. Pinned beats
        // featured so a campaign can override the perma-promote layer.
        $articles = Article::query()
            ->published()
            ->where('blog_category_id', $category->id)
            ->orderByRaw('CASE WHEN pinned_until > ? THEN 0 ELSE 1 END', [now()])
            ->orderByDesc('featured')
            ->latest('published_at')
            ->paginate(12);

        return view('blog.category', compact('category', 'articles'));
    }
}
