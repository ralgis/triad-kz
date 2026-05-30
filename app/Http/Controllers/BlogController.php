<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BlogCategory;
use App\Services\ContentToc;
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

        return view('blog.index', compact('articles', 'featured', 'categories'));
    }

    /**
     * Article detail. Fans out into ContentToc + related-block + breadcrumb
     * trail (including the rubric when present). The exists() re-check
     * through scopePublished is the single source of truth for "visible";
     * router model binding alone doesn't enforce the future-date scope.
     */
    public function show(Article $article, ContentToc $toc): View
    {
        abort_unless(
            Article::published()->whereKey($article->id)->exists(),
            404,
        );

        $article->load(['blogCategory', 'pillar', 'products', 'gosts']);

        $tocItems = $article->toc_enabled ? $toc->extract($article->content ?? '') : [];
        $contentWithAnchors = $toc->injectIds($article->content ?? '');

        $related = $article->relatedInBlogCategory(limit: 4);

        // Pillar-of-cluster: every cluster gets its pillar referenced
        // back. Pillar pages get the list of all their clusters.
        $pillarOfCluster = $article->pillar;
        $clustersOfPillar = $article->is_pillar
            ? $article->clusters()->published()->orderBy('title')->get()
            : collect();

        return view('blog.article', [
            'article' => $article,
            'tocItems' => $tocItems,
            'contentHtml' => $contentWithAnchors,
            'related' => $related,
            'pillarOfCluster' => $pillarOfCluster,
            'clustersOfPillar' => $clustersOfPillar,
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
