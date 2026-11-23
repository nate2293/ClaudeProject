<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Behave as in production even if `npm run dev` is running locally.
        Vite::useHotFile(storage_path('framework/testing/no-hot-file'));
    }

    public function test_shop_pages_get_a_strict_csp_with_a_nonce_for_the_inline_script()
    {
        $response = $this->get(route('home'))->assertOk();

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([A-Za-z0-9]+)'/", $csp);
        preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $nonce);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString('form-action \'self\' https://checkout.stripe.com', $csp);

        // Every inline/script tag on the page carries the nonce.
        preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>/', $response->getContent(), $inlineScripts);
        $this->assertNotEmpty($inlineScripts[0]);
        foreach ($inlineScripts[0] as $tag) {
            $this->assertStringContainsString("nonce=\"{$nonce[1]}\"", $tag);
        }

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_the_admin_area_gets_the_other_headers_but_no_csp()
    {
        $response = $this->get('/admin/login')->assertOk();

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $response->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_is_sent_only_over_https_in_production()
    {
        $this->get(route('home'))->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
