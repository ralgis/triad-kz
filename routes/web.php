<?php

declare(strict_types=1);

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{category:slug}', [CatalogController::class, 'show'])->name('catalog.category');
Route::get('/catalog/{category:slug}/{product:slug}', [ProductController::class, 'show'])->name('catalog.product');

Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/cart/add', [CartController::class, 'add'])
    ->middleware('throttle:60,1')
    ->name('cart.add');
Route::patch('/cart/{productId}', [CartController::class, 'update'])
    ->whereNumber('productId')
    ->name('cart.update');
Route::delete('/cart/{productId}', [CartController::class, 'remove'])
    ->whereNumber('productId')
    ->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:checkout')
    ->name('checkout.store');

Route::get('/order/{order:order_number}', [OrderController::class, 'show'])->name('order.show');
Route::get('/order/{order:order_number}/invoice', [OrderController::class, 'invoice'])->name('order.invoice');

// Env-aware robots.txt — dev blocks all crawlers, prod allows + Sitemap.
Route::get('/robots.txt', RobotsController::class);
