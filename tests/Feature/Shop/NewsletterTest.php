<?php

namespace Tests\Feature\Shop;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_can_subscribe_once()
    {
        $this->post(route('newsletter.store'), ['email' => ' Jane@Example.com '])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('newsletter.store'), ['email' => 'jane@example.com'])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'jane@example.com']);
    }

    public function test_an_invalid_email_is_rejected()
    {
        $this->post(route('newsletter.store'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
    }
}
