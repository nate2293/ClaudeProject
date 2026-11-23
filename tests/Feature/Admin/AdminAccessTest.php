<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/admin', '/admin/orders', '/admin/products', '/admin/products/create', '/admin/categories', '/admin/stripe-events', '/admin/payment-disputes'];

    public function test_guests_are_sent_to_the_admin_login()
    {
        $this->get('/admin/orders')->assertRedirect('/admin/login');
    }

    public function test_customers_cannot_open_the_admin_area()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
    }

    public function test_staff_can_open_every_admin_page()
    {
        $this->actingAs(User::factory()->create()->forceFill(['is_admin' => true]));

        foreach (self::PAGES as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_admin_rights_cannot_be_given_through_registration_or_profile_forms()
    {
        $this->post(route('register'), [
            'name' => 'Mallory', 'email' => 'mallory@example.com', 'password' => 'password', 'password_confirmation' => 'password',
            'is_admin' => true,
        ]);
        $user = User::firstWhere('email', 'mallory@example.com');
        $this->assertFalse($user->is_admin);

        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Mallory', 'email' => 'mallory@example.com', 'is_admin' => true]);
        $this->assertFalse($user->refresh()->is_admin);
    }

    public function test_staff_access_is_granted_and_revoked_from_the_command_line()
    {
        $user = User::factory()->create(['email' => 'jo@example.com']);

        $this->artisan('shop:make-admin', ['email' => 'jo@example.com'])->assertSuccessful();
        $this->assertTrue($user->refresh()->is_admin);

        $this->artisan('shop:make-admin', ['email' => 'jo@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->refresh()->is_admin);

        $this->artisan('shop:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
