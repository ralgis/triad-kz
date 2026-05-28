<?php

declare(strict_types=1);

use App\Http\Controllers\RobotsController;
use Illuminate\Support\Facades\Route;

// Phase 2.1 — foundation routes. Home is a placeholder until Phase 2.2
// fills in HomeController; for now it just verifies the layout renders.
Route::view('/', 'home')->name('home');

// Env-aware robots.txt — dev blocks all crawlers, prod allows + Sitemap.
Route::get('/robots.txt', RobotsController::class);
