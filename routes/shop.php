<?php

use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CatalogueController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\NewsletterController;
use App\Http\Controllers\Shop\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront routes
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

// Catalogue
Route::get('shop', [CatalogueController::class, 'index'])->name('shop.index');
Route::get('shop/{category:slug}', [CatalogueController::class, 'category'])->name('shop.category');
Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Bag
Route::get('cart', [CartController::class, 'index'])->name('cart.index');

// Routes that change the bag take a per-session lock (`block`), so two quick
// requests can't both read the session and overwrite each other's changes.
Route::post('cart/lines', [CartController::class, 'store'])->block()->name('cart.lines.store');
Route::patch('cart/lines/{line}', [CartController::class, 'update'])->block()->name('cart.lines.update');
Route::delete('cart/lines/{line}', [CartController::class, 'destroy'])->block()->name('cart.lines.destroy');

// Checkout (Stripe hosted checkout). The webhook lives in routes/webhooks.php.
Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->block()->name('checkout.store');
Route::get('checkout/success', [CheckoutController::class, 'success'])->block()->name('checkout.success');

// Newsletter
Route::post('newsletter', [NewsletterController::class, 'store'])->middleware('throttle:5,1')->name('newsletter.store');

// Customer information pages
Route::inertia('delivery', 'info/delivery')->name('pages.delivery');
Route::inertia('returns', 'info/returns')->name('pages.returns');
Route::inertia('size-guide', 'info/size-guide')->name('pages.size-guide');
Route::inertia('contact', 'info/contact')->name('pages.contact');
Route::inertia('about', 'info/about')->name('pages.about');
Route::inertia('privacy', 'info/privacy')->name('pages.privacy');
Route::inertia('terms', 'info/terms')->name('pages.terms');
