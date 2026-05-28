<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutFormRequest;
use App\Services\Cart\Cart;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * GET /checkout — render the form (redirects to /cart if cart is empty).
 * POST /checkout — validate via CheckoutFormRequest, hand off to
 *                 OrderService::create which handles transaction, PDF,
 *                 email and cart clearing.
 *
 * No state lives on this controller — everything flows
 * Cart → CheckoutData (DTO) → OrderService → Order. Keeps controllers
 * thin and testable.
 */
final class CheckoutController extends Controller
{
    public function show(Cart $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()
                ->route('cart.show')
                ->with('cart.empty', 'Корзина пуста. Добавьте товары перед оформлением.');
        }

        return view('checkout.form', ['cart' => $cart]);
    }

    public function store(CheckoutFormRequest $request, Cart $cart, OrderService $orders): RedirectResponse
    {
        // Defence in depth — the form is gated by show() above but a
        // direct POST with empty cart would otherwise NPE in OrderService.
        if ($cart->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $order = $orders->create($request->toCheckoutData(), $cart);

        return redirect()->route('order.show', ['order' => $order->order_number]);
    }
}
