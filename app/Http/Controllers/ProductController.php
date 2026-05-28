<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

final class ProductController extends Controller
{
    /**
     * Product detail at /catalog/{category-slug}/{product-slug}.
     *
     * Both slugs are bound separately rather than parsing a single
     * compound path so the route stays declarative and Laravel does
     * the slug → model translation. We then verify the product
     * actually belongs to the URL category — otherwise
     * /catalog/wrong-cat/some-product would 200 and dilute SEO with
     * duplicate URLs.
     */
    public function show(Category $category, Product $product): View
    {
        abort_unless($category->published && $product->published, 404);

        if (! $product->categories->contains('id', $category->id)) {
            abort(404);
        }

        return view('catalog.product', compact('category', 'product'));
    }
}
