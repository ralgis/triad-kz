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

        // Eager-load the two related-products blocks. complementaryProducts
        // is the admin-curated cross-category set; relatedInCategory is
        // computed below using its own with() chain.
        $product->load([
            'complementaryProducts' => fn ($q) => $q
                ->where('products.published', true)
                ->where('products.listed', true)
                ->with(['categories:id,slug', 'gosts']),
        ]);

        $complementary = $product->complementaryProducts;
        $alsoInCategory = $product->relatedInCategory(
            limit: 6,
            exclude: $complementary->pluck('id')->all(),
        );

        return view('catalog.product', compact('category', 'product', 'complementary', 'alsoInCategory'));
    }
}
