<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The starter kit's dashboard was replaced by the header's account menu. */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_dashboard_links_go_back_to_the_shop()
    {
        $this->get('/dashboard')->assertRedirect('/');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/');
    }
}
