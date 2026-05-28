<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 2.2 ships only the @add endpoint so product cards/detail pages
 * have a working «В корзину» button. The /cart/ display view + remove/
 * update endpoints land in Phase 2.3.
 *
 * Why split: keeping the Cart-add wire-up close to the catalog work
 * means we can test the catalog→cart hop end-to-end without waiting
 * for the cart UI. Phase 2.3 will replace the redirect-back with
 * redirect-to-cart.
 */
final class CartController extends Controller
{
    public function add(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $product = Product::query()->published()->findOrFail($data['product_id']);
        $cart->add($product, (int) ($data['qty'] ?? 1));

        return back()->with('cart.added', $product->name);
    }
}
