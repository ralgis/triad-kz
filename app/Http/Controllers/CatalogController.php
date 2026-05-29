<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

/**
 * Catalog browse — root list + single category. Product detail is
 * handled separately (ProductController) because its URL nests under
 * the category slug.
 */
final class CatalogController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('published', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('catalog.index', compact('categories'));
    }

    public function show(Category $category): View
    {
        // Avoid leaking unpublished categories via direct URL.
        abort_unless($category->published, 404);

        $children = $category->children()
            ->where('published', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // Calling ->published() on a BelongsToMany trips Larastan's
        // method-resolution; inlining the same predicate sidesteps it
        // and stays in sync with Product::scopePublished.
        $products = $category->products()
            ->where('products.published', true)
            ->with('gosts')
            ->orderBy('products.name')
            ->paginate(12)
            ->withQueryString();

        return view('catalog.category', compact('category', 'children', 'products'));
    }
}
