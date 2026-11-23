<?php

namespace Tests\Feature\Account;

use App\Mail\ConfirmNewsletterSubscription;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AccountPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);
    }

    private function googleUser(string $id = '1001'): GoogleUser
    {
        return (new GoogleUser)->setRaw(['sub' => $id, 'email' => 'jane@gmail.com', 'email_verified' => true])
            ->map(['id' => $id, 'email' => 'jane@gmail.com', 'name' => 'Jane', 'avatar' => null]);
    }

    public function test_a_verified_customer_subscribes_in_one_click_and_can_unsubscribe()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('account.contact'))->assertInertia(fn (Assert $page) => $page->where('newsletter', 'off'));

        $this->post(route('account.newsletter.subscribe'));
        $this->assertTrue(NewsletterSubscriber::firstWhere('email', $user->email)->isMailable());
        Mail::assertNothingQueued();
        $this->get(route('account.contact'))->assertInertia(fn (Assert $page) => $page->where('newsletter', 'subscribed'));

        $this->delete(route('account.newsletter.unsubscribe'));
        $this->get(route('account.contact'))->assertInertia(fn (Assert $page) => $page->where('newsletter', 'off'));
    }

    public function test_an_unverified_email_must_confirm_by_email_first()
    {
        $this->actingAs(User::factory()->unverified()->create());

        $this->post(route('account.newsletter.subscribe'));

        Mail::assertQueued(ConfirmNewsletterSubscription::class);
        $this->get(route('account.contact'))->assertInertia(fn (Assert $page) => $page->where('newsletter', 'pending'));
    }

    public function test_google_can_be_connected_from_the_account()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Socialite::fake('google', $this->googleUser());

        $this->get(route('auth.google.redirect'));
        $this->get(route('auth.google.callback', ['state' => 'x', 'code' => 'y']))->assertRedirect(route('account.social', absolute: false));

        $this->assertSame('1001', $user->refresh()->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_google_account_already_used_elsewhere_cannot_be_connected()
    {
        User::factory()->create()->forceFill(['google_id' => '1001'])->save();
        $user = User::factory()->create();
        $this->actingAs($user);
        Socialite::fake('google', $this->googleUser());

        $this->get(route('auth.google.redirect'));
        $this->get(route('auth.google.callback', ['state' => 'x', 'code' => 'y']))->assertSessionHas('error');

        $this->assertNull($user->refresh()->google_id);
    }

    public function test_google_can_be_disconnected_only_when_a_password_is_set()
    {
        $googleOnly = User::factory()->create();
        $googleOnly->forceFill(['google_id' => '1001', 'password' => null])->save();

        $this->actingAs($googleOnly)->delete(route('account.social.google.disconnect'))->assertSessionHas('error');
        $this->assertSame('1001', $googleOnly->refresh()->google_id);

        $withPassword = User::factory()->create();
        $withPassword->forceFill(['google_id' => '2002'])->save();
        $this->actingAs($withPassword)->delete(route('account.social.google.disconnect'))->assertSessionHas('success');
        $this->assertNull($withPassword->refresh()->google_id);
    }

    public function test_every_account_page_renders()
    {
        $this->actingAs(User::factory()->create());

        foreach (['account.orders' => 'account/orders/index', 'profile.edit' => 'settings/profile', 'password.edit' => 'settings/password',
            'account.contact' => 'account/contact-preferences', 'account.social' => 'account/social-accounts', 'appearance' => 'settings/appearance'] as $route => $component) {
            $this->get(route($route))->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }
}
