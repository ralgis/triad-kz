<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CartController extends Controller
{
    public function show(Cart $cart): View
    {
        return view('cart', ['cart' => $cart]);
    }

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

    public function update(Request $request, Cart $cart, int $productId): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        // qty=0 removes the line; matches Cart::update's documented contract
        // and lets the cart form ship a single PATCH for both update + remove.
        $cart->update($productId, (int) $data['qty']);

        return redirect()->route('cart.show');
    }

    public function remove(Cart $cart, int $productId): RedirectResponse
    {
        $cart->remove($productId);

        return redirect()->route('cart.show');
    }
}
