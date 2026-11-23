<?php

namespace Tests\Feature\Auth;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleSignInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'test-client.apps.googleusercontent.com', 'services.google.client_secret' => 'test-secret']);
    }

    private function googleUser(array $overrides = []): GoogleUser
    {
        $data = ['sub' => '1001', 'email' => 'jane@gmail.com', 'email_verified' => true, 'name' => 'Jane Doe', 'picture' => 'https://lh3.googleusercontent.com/a/photo', ...$overrides];

        return (new GoogleUser)->setRaw($data)->map([
            'id' => $data['sub'], 'email' => $data['email'], 'name' => $data['name'], 'avatar' => $data['picture'],
        ]);
    }

    /** Click "Continue with Google" on a page, then come back from Google. */
    private function signInFrom(string $page, ?GoogleUser $user = null)
    {
        Socialite::fake('google', $user ?? $this->googleUser());
        $this->get(route('auth.google.redirect', ['return' => $page]));

        return $this->get(route('auth.google.callback', ['state' => 'x', 'code' => 'y']));
    }

    public function test_the_button_sends_the_shopper_to_google_with_the_account_chooser()
    {
        $response = $this->get(route('auth.google.redirect', ['return' => '/shop/women']));

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $location);
        $this->assertStringContainsString('prompt=select_account', $location);
        $this->assertStringContainsString(urlencode(url('/auth/google/callback')), $location);
        $this->assertSame(url('/shop/women'), session('url.intended'));
    }

    public function test_a_new_shopper_gets_an_account_and_returns_to_the_page_they_were_on()
    {
        $this->signInFrom('/shop/women?sort=newest')->assertRedirect('/shop/women?sort=newest');

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['1001', 'jane@gmail.com', 'Jane Doe'], [$user->google_id, $user->email, $user->name]);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->hasPassword());
        $this->assertFalse($user->is_admin);
        $this->assertSame('https://lh3.googleusercontent.com/a/photo', $user->avatar_url);
    }

    public function test_a_returning_shopper_is_matched_by_google_id_even_if_their_email_changed()
    {
        $user = User::factory()->create(['email' => 'old@gmail.com']);
        $user->forceFill(['google_id' => '1001'])->save();

        $this->signInFrom('/', $this->googleUser(['email' => 'new@gmail.com']))->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_google_is_linked_to_an_existing_verified_account_which_keeps_its_password()
    {
        $user = User::factory()->create(['email' => 'jane@gmail.com', 'password' => Hash::make('secret-password')]);

        $this->signInFrom('/')->assertSessionHas('success', 'Signed in as '.$user->name.'.');

        $user->refresh();
        $this->assertSame('1001', $user->google_id);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_linking_an_unverified_account_revokes_its_password_and_sessions()
    {
        // Someone registered with Jane's address before she ever used Google.
        $squatter = User::factory()->unverified()->create(['email' => 'jane@gmail.com', 'password' => Hash::make('squatter-pass')]);
        $this->actingAs($squatter)->withSession(['auth.signed_in_at' => now()->subHour()->getTimestamp()]);
        $this->get(route('profile.edit'))->assertOk();
        auth()->logout();

        $this->signInFrom('/')->assertSessionHas('success', fn (string $message) => str_contains($message, 'removed the old password'));

        $squatter->refresh();
        $this->assertNull($squatter->password);
        $this->assertNotNull($squatter->email_verified_at);
        $this->assertNotNull($squatter->sessions_revoked_at);

        // The old session (signed in an hour ago) is now signed out...
        $this->actingAs($squatter)->withSession(['auth.signed_in_at' => now()->subHour()->getTimestamp()])
            ->get(route('profile.edit'))->assertRedirect(route('home'));
        $this->assertGuest();

        // ...and the old password no longer works.
        $this->post(route('login'), ['email' => 'jane@gmail.com', 'password' => 'squatter-pass']);
        $this->assertGuest();
    }

    public function test_the_bag_survives_signing_in()
    {
        $product = Product::factory()->create();
        $this->post(route('cart.lines.store'), ['product_id' => $product->id, 'colour' => 'Black', 'size' => 'M', 'quantity' => 2]);

        $this->signInFrom('/cart');

        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page->where('cart.count', 2)->where('auth.user.email', 'jane@gmail.com'));
    }

    public function test_unverified_google_emails_are_refused()
    {
        $this->signInFrom('/', $this->googleUser(['email_verified' => false]))
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_cancelling_on_google_returns_to_the_page_with_a_message()
    {
        $this->get(route('auth.google.redirect', ['return' => '/shop/men']));

        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect('/shop/men')
            ->assertSessionHas('error', 'Google sign-in was cancelled.');
        $this->assertGuest();
    }

    public function test_a_tampered_or_expired_request_is_refused()
    {
        Socialite::fake('google', fn () => throw new InvalidStateException);

        $this->get(route('auth.google.callback', ['state' => 'forged', 'code' => 'y']))->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_return_addresses_off_this_site_are_ignored()
    {
        foreach (['https://evil.example/', '//evil.example/', '/\\evil.example', 'javascript:alert(1)', '/login', '/admin'] as $return) {
            $this->flushSession();
            $this->get(route('auth.google.redirect', ['return' => $return]));
            $this->assertNull(session('url.intended'), "Accepted {$return}");
        }
    }

    public function test_the_button_explains_itself_when_google_is_not_configured()
    {
        config(['services.google.client_id' => null]);

        $this->get(route('auth.google.redirect', ['return' => '/shop']))
            ->assertRedirect('/shop')
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'sign in with your email'));
    }

    public function test_email_sign_in_also_returns_to_the_page_the_shopper_came_from()
    {
        $user = User::factory()->create();

        $this->get(route('login', ['return' => '/products/linen-shirt']));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect('/products/linen-shirt');
    }
}
