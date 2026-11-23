<?php

use App\Http\Controllers\Shop\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook routes
|--------------------------------------------------------------------------
|
| Loaded outside the `web` middleware group (see bootstrap/app.php): no
| session, cookies, CSRF or Inertia. Each endpoint authenticates the caller
| itself, e.g. by verifying the provider's signature.
|
*/

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
