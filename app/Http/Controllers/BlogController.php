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
        $articles = Article::query()
            ->published()
            ->with('blogCategory')
            ->latest('published_at')
            ->paginate(12);

        // Sidebar — rubrics with article counts for the listed/published set.
        // Eagerly loaded into the index view so layout doesn't N+1.
        $categories = BlogCategory::query()
            ->published()
            ->listed()
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderBy('order')
            ->get();

        return view('blog.index', compact('articles', 'categories'));
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

        $article->load('blogCategory');

        $tocItems = $toc->extract($article->content ?? '');
        $contentWithAnchors = $toc->injectIds($article->content ?? '');

        $related = $article->relatedInBlogCategory(limit: 4);

        return view('blog.article', [
            'article' => $article,
            'tocItems' => $tocItems,
            'contentHtml' => $contentWithAnchors,
            'related' => $related,
        ]);
    }

    public function category(BlogCategory $category): View
    {
        abort_unless($category->published, 404);

        $articles = Article::query()
            ->published()
            ->where('blog_category_id', $category->id)
            ->latest('published_at')
            ->paginate(12);

        return view('blog.category', compact('category', 'articles'));
    }
}
