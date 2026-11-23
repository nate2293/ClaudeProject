<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/** Newsletter on/off for the signed-in customer's email. */
class ContactPreferencesController extends Controller
{
    public function show(Request $request): Response
    {
        $subscriber = NewsletterSubscriber::firstWhere('email', $request->user()->email);

        return Inertia::render('account/contact-preferences', [
            'newsletter' => match (true) {
                $subscriber?->isMailable() => 'subscribed',
                $subscriber && $subscriber->unsubscribed_at === null && $subscriber->token !== null => 'pending',
                default => 'off',
            },
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $user = $request->user();
        $subscriber = NewsletterSubscriber::createOrFirst(['email' => $user->email]);

        if ($subscriber->isMailable()) {
            return back();
        }

        $subscriber->startOptIn();

        // A verified email has already proved it belongs to this customer, so ticking
        // the box is enough. Otherwise confirm by email, as for any sign-up.
        if ($user->email_verified_at) {
            $subscriber->forceFill(['confirmed_at' => now()])->save();

            return back()->with('success', 'You’re subscribed to the newsletter.');
        }

        Mail::to($subscriber->email)->queue(new ConfirmNewsletterSubscription($subscriber));

        return back()->with('success', 'Check your inbox and click the link to confirm.');
    }

    public function unsubscribe(Request $request): RedirectResponse
    {
        NewsletterSubscriber::where('email', $request->user()->email)
            ->whereNull('unsubscribed_at')
            ->update(['unsubscribed_at' => now()]);

        return back()->with('success', 'You won’t receive the newsletter any more.');
    }
}
