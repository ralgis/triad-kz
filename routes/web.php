<?php

declare(strict_types=1);

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{category:slug}', [CatalogController::class, 'show'])->name('catalog.category');
Route::get('/catalog/{category:slug}/{product:slug}', [ProductController::class, 'show'])->name('catalog.product');

// Cart-add wires up Phase 2.2 «В корзину» buttons; /cart/ display lands in 2.3.
Route::post('/cart/add', [CartController::class, 'add'])
    ->middleware('throttle:60,1')
    ->name('cart.add');

// Env-aware robots.txt — dev blocks all crawlers, prod allows + Sitemap.
Route::get('/robots.txt', RobotsController::class);
