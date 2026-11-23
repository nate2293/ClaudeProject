<?php

use App\Http\Controllers\Account\ContactPreferencesController;
use App\Http\Controllers\Account\OrderController;
use App\Http\Controllers\Account\SocialAccountsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| The signed-in customer's account: orders, details and preferences.
*/

Route::middleware('auth')->prefix('account')->group(function () {
    Route::redirect('/', '/account/orders')->name('account');

    Route::get('orders', [OrderController::class, 'index'])->name('account.orders');
    Route::get('orders/{reference}', [OrderController::class, 'show'])->name('account.orders.show');

    Route::get('details', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('details', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('details', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('contact-preferences', [ContactPreferencesController::class, 'show'])->name('account.contact');
    Route::post('contact-preferences/newsletter', [ContactPreferencesController::class, 'subscribe'])->name('account.newsletter.subscribe');
    Route::delete('contact-preferences/newsletter', [ContactPreferencesController::class, 'unsubscribe'])->name('account.newsletter.unsubscribe');

    Route::get('social-accounts', [SocialAccountsController::class, 'show'])->name('account.social');
    Route::delete('social-accounts/google', [SocialAccountsController::class, 'disconnectGoogle'])->name('account.social.google.disconnect');

    Route::get('appearance', fn () => Inertia::render('settings/appearance'))->name('appearance');
});

// Old starter-kit addresses.
Route::redirect('settings', '/account/details');
Route::redirect('settings/profile', '/account/details');
Route::redirect('settings/password', '/account/password');
Route::redirect('settings/appearance', '/account/appearance');
