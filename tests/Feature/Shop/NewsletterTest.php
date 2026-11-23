<?php

namespace Tests\Feature\Shop;

use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Double opt-in and one-click unsubscribe (UK PECR / GDPR). */
class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function subscribe(string $email = 'jane@example.com')
    {
        return $this->post(route('newsletter.store'), ['email' => $email]);
    }

    public function test_signing_up_sends_a_confirmation_and_nothing_counts_until_it_is_clicked()
    {
        $this->subscribe(' Jane@Example.com ')->assertRedirect()->assertSessionHasNoErrors();

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('jane@example.com', $subscriber->email);
        $this->assertFalse($subscriber->isMailable());
        $this->assertSame(0, NewsletterSubscriber::mailable()->count());
        Mail::assertQueued(ConfirmNewsletterSubscription::class, fn ($mail) => $mail->hasTo('jane@example.com'));

        $this->get($subscriber->confirmUrl())->assertInertia(fn (Assert $page) => $page->component('newsletter/status')->where('state', 'confirmed'));

        $this->assertTrue($subscriber->refresh()->isMailable());
    }

    public function test_confirmed_subscribers_are_not_emailed_again_and_the_response_is_the_same()
    {
        $this->subscribe();
        $this->get(NewsletterSubscriber::sole()->confirmUrl());

        $this->subscribe()->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertQueued(ConfirmNewsletterSubscription::class, 1);
        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_unsubscribing_works_from_a_link_and_from_one_click_mail_clients()
    {
        $this->subscribe();
        $subscriber = NewsletterSubscriber::sole();
        $this->get($subscriber->confirmUrl());

        $this->get($subscriber->unsubscribeUrl())->assertInertia(fn (Assert $page) => $page->where('state', 'unsubscribed'));
        $this->assertFalse($subscriber->refresh()->isMailable());

        // RFC 8058 one-click: a POST with no CSRF token.
        $this->post($subscriber->unsubscribeUrl())->assertOk();
    }

    public function test_signing_up_again_after_unsubscribing_needs_a_fresh_confirmation()
    {
        $this->subscribe();
        $subscriber = NewsletterSubscriber::sole();
        $oldConfirm = $subscriber->confirmUrl();
        $this->get($oldConfirm);
        $this->get($subscriber->unsubscribeUrl());

        $this->subscribe();

        $subscriber->refresh();
        $this->assertNull($subscriber->unsubscribed_at);
        $this->assertFalse($subscriber->isMailable());
        $this->get($oldConfirm)->assertInertia(fn (Assert $page) => $page->where('state', 'invalid'));
        Mail::assertQueued(ConfirmNewsletterSubscription::class, 2);
    }

    public function test_unknown_links_and_expired_sign_ups()
    {
        $this->get(route('newsletter.confirm', str_repeat('x', 64)))->assertInertia(fn (Assert $page) => $page->where('state', 'invalid'));
        $this->get(route('newsletter.unsubscribe', 'short'))->assertInertia(fn (Assert $page) => $page->where('state', 'invalid'));

        $this->subscribe();
        $this->travel(NewsletterSubscriber::CONFIRM_WITHIN_DAYS + 1)->days();
        $this->artisan('model:prune', ['--model' => [NewsletterSubscriber::class]]);

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_an_invalid_email_is_rejected()
    {
        $this->post(route('newsletter.store'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        Mail::assertNothingQueued();
    }
}
