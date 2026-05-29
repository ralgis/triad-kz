<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

/**
 * Public homepage. Hero + featured categories + featured products + latest
 * articles. Each section uses the model's `published`/`featured` scopes so
 * unpublished drafts never leak — admins control the homepage by toggling
 * those flags in Filament.
 */
final class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('published', true)
            ->where('listed', true)
            ->orderBy('order')
            ->limit(8)
            ->get();

        $products = Product::query()
            ->published()
            ->listed()
            ->featured()
            ->with(['categories:id,slug', 'gosts'])
            ->limit(6)
            ->get();

        $articles = Article::query()
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('home', compact('categories', 'products', 'articles'));
    }
}
