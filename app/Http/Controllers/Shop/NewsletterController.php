<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\SubscribeToNewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    public function store(SubscribeToNewsletterRequest $request): RedirectResponse
    {
        // Same response whether or not the address already exists, so the form
        // can't be used to discover who is subscribed.
        // createOrFirst relies on the unique index, so two simultaneous sign-ups for
        // the same address can't race into a duplicate-key error.
        NewsletterSubscriber::createOrFirst(['email' => $request->validated('email')]);

        // The form shows its own inline confirmation, so no flash message is needed.
        return back();
    }
}
