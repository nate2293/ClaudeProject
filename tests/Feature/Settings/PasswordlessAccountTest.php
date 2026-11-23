<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Customers who signed up with Google have no password. */
class PasswordlessAccountTest extends TestCase
{
    use RefreshDatabase;

    private function googleOnlyUser(): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->forceFill(['password' => null, 'google_id' => '1001'])->save());
    }

    public function test_they_can_set_a_first_password_without_a_current_one()
    {
        $user = $this->googleOnlyUser();
        $this->actingAs($user);

        $this->get(route('password.edit'))->assertInertia(fn (Assert $page) => $page->where('auth.hasPassword', false));

        $this->put(route('password.update'), ['password' => 'new-strong-password', 'password_confirmation' => 'new-strong-password'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-strong-password', $user->refresh()->password));
    }

    public function test_once_set_the_current_password_is_required_again()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->put(route('password.update'), ['password' => 'another-password', 'password_confirmation' => 'another-password'])
            ->assertSessionHasErrors('current_password');
    }

    public function test_they_delete_their_account_by_typing_their_email()
    {
        $user = $this->googleOnlyUser();
        $this->actingAs($user);

        $this->delete(route('profile.destroy'), ['email' => 'wrong@example.com'])->assertSessionHasErrors('email');
        $this->assertNotNull($user->fresh());

        $this->delete(route('profile.destroy'), ['email' => strtoupper($user->email)])->assertRedirect('/');
        $this->assertNull($user->fresh());
        $this->assertGuest();
    }

    public function test_password_sign_in_is_refused_for_accounts_without_one()
    {
        $user = $this->googleOnlyUser();

        $this->post(route('login'), ['email' => $user->email, 'password' => '']);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'anything']);

        $this->assertGuest();
    }

    public function test_settings_pages_render_inside_the_shop()
    {
        $this->actingAs(User::factory()->create());

        foreach (['profile.edit', 'password.edit', 'appearance'] as $page) {
            $this->get(route($page))->assertOk()->assertInertia(fn (Assert $p) => $p->has('navigation')->has('cart'));
        }
    }
}
