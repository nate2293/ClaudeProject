<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The starter kit's dashboard and settings addresses now lead to the account area. */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_addresses_lead_to_the_account_area()
    {
        $this->get('/dashboard')->assertRedirect('/account/orders');
        $this->get('/settings/profile')->assertRedirect('/account/details');
        $this->get('/settings/password')->assertRedirect('/account/password');

        $this->get('/account/orders')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/account/orders')->assertOk();
    }
}
