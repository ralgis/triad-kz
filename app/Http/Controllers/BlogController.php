<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;

final class BlogController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->published()
            ->latest('published_at')
            ->paginate(12);

        return view('blog.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        // scopePublished is the source of truth for "visible" (not-null
        // published_at AND not in the future); re-querying through it
        // here keeps that check in one place and stays in sync if the
        // visibility rule ever grows a third clause.
        abort_unless(
            Article::published()->whereKey($article->id)->exists(),
            404,
        );

        return view('blog.article', compact('article'));
    }
}
