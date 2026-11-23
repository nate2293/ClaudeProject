<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentException;
use App\Payments\Stripe\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Exercises the real Stripe gateway against a fake HTTP client: checks the
 * request we send and how we interpret Stripe's responses, without a network.
 */
class StripeGatewayTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeHttpClient $http;

    private StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new FakeStripeHttpClient;
        ApiRequestor::setHttpClient($this->http);
        $this->gateway = new StripeGateway(new StripeClient('sk_test_fake'));
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    public function test_create_checkout_sends_order_lines_shipping_and_currency()
    {
        $order = Order::create(['currency' => 'eur', 'subtotal' => 6000, 'shipping' => 495, 'total' => 6495]);
        $order->items()->create([
            'product_name' => 'Merino Jumper', 'colour' => 'Oat', 'size' => 'M',
            'unit_price' => 3000, 'quantity' => 2, 'line_total' => 6000,
        ]);
        $this->http->respondWith(['id' => 'cs_test_1', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.com/c/cs_test_1']);

        $session = $this->gateway->createCheckout($order->load('items'));

        $this->assertSame('cs_test_1', $session->id);
        $this->assertSame('https://checkout.stripe.com/c/cs_test_1', $session->url);

        $request = $this->http->requests[0];
        $this->assertSame('post', $request['method']);
        $this->assertStringEndsWith('/v1/checkout/sessions', $request['url']);
        $this->assertContains("Idempotency-Key: checkout-{$order->reference}", $request['headers']);

        $params = $request['params'];
        $this->assertSame('payment', $params['mode']);
        $this->assertSame($order->reference, $params['client_reference_id']);
        $this->assertSame('eur', $params['line_items'][0]['price_data']['currency']);
        $this->assertSame(3000, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(2, $params['line_items'][0]['quantity']);
        $this->assertSame(495, $params['shipping_options'][0]['shipping_rate_data']['fixed_amount']['amount']);
        $this->assertStringContainsString('{CHECKOUT_SESSION_ID}', $params['success_url']);
        // The payment page (and so the stock reservation) expires just over 30 minutes from now.
        $this->assertEqualsWithDelta(now()->addMinutes(31)->getTimestamp(), $params['expires_at'], 5);
    }

    public function test_a_paid_session_is_mapped_with_customer_and_shipping_details()
    {
        $this->http->respondWith([
            'id' => 'cs_test_1', 'object' => 'checkout.session', 'status' => 'complete', 'payment_status' => 'paid',
            'payment_intent' => 'pi_123', 'amount_total' => 6495, 'total_details' => ['amount_shipping' => 495],
            'customer_details' => ['email' => 'jane@example.com', 'name' => 'Jane Billing'],
            'collected_information' => ['shipping_details' => ['name' => 'Jane Shipping', 'address' => ['city' => 'Leeds']]],
        ]);

        $result = $this->gateway->retrieveCheckout('cs_test_1');

        $this->assertSame(CheckoutStatus::Paid, $result->status);
        $this->assertSame('jane@example.com', $result->email);
        $this->assertSame('Jane Shipping', $result->customerName);
        $this->assertSame('Leeds', $result->shippingAddress['city']);
        $this->assertSame('pi_123', $result->paymentReference);
        $this->assertSame(6495, $result->amountTotal);
        $this->assertSame(495, $result->amountShipping);
    }

    public function test_older_api_versions_with_top_level_shipping_details_are_supported()
    {
        $this->http->respondWith([
            'id' => 'cs_test_1', 'object' => 'checkout.session', 'status' => 'complete', 'payment_status' => 'paid',
            'shipping_details' => ['name' => 'Old Format', 'address' => ['city' => 'York']],
        ]);

        $result = $this->gateway->retrieveCheckout('cs_test_1');

        $this->assertSame('Old Format', $result->customerName);
        $this->assertSame('York', $result->shippingAddress['city']);
    }

    public function test_open_processing_and_expired_sessions_are_mapped()
    {
        $this->http->respondWith(['id' => 'cs_0', 'object' => 'checkout.session', 'status' => 'open', 'payment_status' => 'unpaid']);
        $this->assertSame(CheckoutStatus::Pending, $this->gateway->retrieveCheckout('cs_0')->status);

        // Completed with a delayed method (e.g. Bacs): the payment is in flight.
        $this->http->respondWith(['id' => 'cs_1', 'object' => 'checkout.session', 'status' => 'complete', 'payment_status' => 'unpaid']);
        $this->assertSame(CheckoutStatus::Processing, $this->gateway->retrieveCheckout('cs_1')->status);

        $this->http->respondWith(['id' => 'cs_2', 'object' => 'checkout.session', 'status' => 'expired', 'payment_status' => 'unpaid']);
        $this->assertSame(CheckoutStatus::Expired, $this->gateway->retrieveCheckout('cs_2')->status);
    }

    public function test_expire_checkout_calls_the_expire_endpoint()
    {
        $this->http->respondWith(['id' => 'cs_test_1', 'object' => 'checkout.session', 'status' => 'expired']);

        $this->gateway->expireCheckout('cs_test_1');

        $this->assertStringEndsWith('/v1/checkout/sessions/cs_test_1/expire', $this->http->requests[0]['url']);
    }

    public function test_stripe_errors_become_payment_exceptions()
    {
        $this->http->respondWith(['error' => ['type' => 'invalid_request_error', 'message' => 'No such session']], 404);

        $this->expectException(PaymentException::class);

        $this->gateway->retrieveCheckout('cs_missing');
    }

    public function test_a_missing_secret_is_reported_clearly()
    {
        $this->expectExceptionMessage('Payments are not configured');

        (new StripeGateway(null))->retrieveCheckout('cs_test_1');
    }
}

/** Records Stripe API requests and replays a canned JSON response. */
class FakeStripeHttpClient implements ClientInterface
{
    /** @var list<array{method: string, url: string, headers: array, params: array}> */
    public array $requests = [];

    private string $body = '{}';

    private int $status = 200;

    public function respondWith(array $body, int $status = 200): void
    {
        $this->body = json_encode($body);
        $this->status = $status;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $this->requests[] = ['method' => $method, 'url' => $absUrl, 'headers' => $headers, 'params' => $params];

        return [$this->body, $this->status, []];
    }
}
