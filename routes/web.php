<?php

declare(strict_types=1);

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GostController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndexNowKeyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
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

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
// Category route MUST be declared before {article:slug} so /blog/category/{slug}
// doesn't get shadowed by the article catch-all. Same trick as Page slug
// catch-all at the bottom of the file.
Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{article:slug}', [BlogController::class, 'show'])->name('blog.article');

Route::get('/contacts', [ContactController::class, 'show'])->name('contacts.show');
Route::post('/contacts', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contacts.store');

// Standards reference («ГОСТы и Серии») — single-page accordion. Detail
// links use #slug anchors; no /gosts/{slug} routes by design (avoids
// fighting the {page:slug} catch-all and keeps SEO weight on one URL).
Route::get('/gosts', [GostController::class, 'index'])->name('gosts.index');

// Env-aware robots.txt — dev blocks all crawlers, prod allows + Sitemap.
Route::get('/robots.txt', RobotsController::class);

// Sitemap built dynamically from published Categories / Products /
// Articles / Pages. Cached at the HTTP layer in front of Plesk (will
// land in deploy config); the request itself is a handful of indexed
// SELECTs.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// IndexNow ownership verification key. Search engines fetch /{key}.txt
// after we POST to api.indexnow.org/IndexNow; body MUST equal the key.
// {key} constrained to 32-128 hex/alphanumeric chars to avoid shadowing
// any other .txt file we might add later.
Route::get('/{key}.txt', IndexNowKeyController::class)
    ->where('key', '[a-zA-Z0-9-]{8,128}')
    ->name('indexnow.key');

// Catch-all for content pages (about / gosts / payment / ...). MUST be
// last — Laravel matches top-down and {page:slug} would otherwise
// shadow every other GET above. Constrained to kebab-case so /robots.txt
// and other file-extension paths don't accidentally match.
Route::get('/{page:slug}', [PageController::class, 'show'])
    ->where('page', '[a-z][a-z0-9-]*')
    ->name('page.show');
