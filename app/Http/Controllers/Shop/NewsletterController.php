<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\SubscribeToNewsletterRequest;
use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Double opt-in newsletter sign-up, as UK PECR/GDPR expect: an address joins
 * the list only after its owner clicks the emailed link, and every email can
 * unsubscribe in one click.
 */
class NewsletterController extends Controller
{
    public function store(SubscribeToNewsletterRequest $request): RedirectResponse
    {
        // createOrFirst relies on the unique index, so two simultaneous sign-ups for
        // the same address can't race into a duplicate-key error.
        $subscriber = NewsletterSubscriber::createOrFirst(['email' => $request->validated('email')]);

        // Already on the list: nothing to do. Otherwise (new, unconfirmed or previously
        // unsubscribed) send a fresh confirmation link.
        if (! $subscriber->isMailable()) {
            $subscriber->startOptIn();
            Mail::to($subscriber->email)->queue(new ConfirmNewsletterSubscription($subscriber));
        }

        // Same response whatever the address's state, so the form can't be used to
        // discover who is subscribed. The form shows its own inline message.
        return back();
    }

    public function confirm(string $token): Response
    {
        $subscriber = $this->findByToken($token);

        if ($subscriber && $subscriber->unsubscribed_at === null) {
            $subscriber->confirmed_at ??= now();
            $subscriber->save();
        }

        return $this->status($subscriber && $subscriber->isMailable() ? 'confirmed' : 'invalid');
    }

    /**
     * Unsubscribe in one click. GET for the link in an email; POST for mail
     * clients' one-click unsubscribe (RFC 8058, List-Unsubscribe-Post).
     */
    public function unsubscribe(string $token): Response
    {
        $subscriber = $this->findByToken($token);

        if ($subscriber) {
            $subscriber->unsubscribed_at ??= now();
            $subscriber->save();
        }

        return $this->status($subscriber ? 'unsubscribed' : 'invalid');
    }

    private function findByToken(string $token): ?NewsletterSubscriber
    {
        return strlen($token) === 64 ? NewsletterSubscriber::firstWhere('token', $token) : null;
    }

    private function status(string $state): Response
    {
        return Inertia::render('newsletter/status', ['state' => $state]);
    }
}
