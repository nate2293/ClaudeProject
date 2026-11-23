<?php

namespace Tests\Fakes;

use App\Models\Order;
use App\Payments\CheckoutResult;
use App\Payments\CheckoutSession;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;

/**
 * In-memory payment gateway for tests. Records created sessions and returns
 * whatever outcome the test configures.
 */
class FakePaymentGateway implements PaymentGateway
{
    /** @var list<Order> */
    public array $createdFor = [];

    public CheckoutStatus $status = CheckoutStatus::Paid;

    /** @var array<string, CheckoutStatus> Per-session overrides of `$status`. */
    public array $statusBySession = [];

    /** @var list<string> Session IDs whose retrieval throws, simulating provider errors. */
    public array $failRetrieveFor = [];

    /** What the provider reports as charged; null mimics an omitted amount. */
    public ?int $amountTotal = null;

    public bool $failOnCreate = false;

    public function createCheckout(Order $order): CheckoutSession
    {
        if ($this->failOnCreate) {
            throw new PaymentException('Simulated provider failure.');
        }

        $this->createdFor[] = $order;

        return new CheckoutSession(id: "cs_test_{$order->reference}", url: "https://checkout.stripe.test/{$order->reference}");
    }

    /** @var list<string> */
    public array $expired = [];

    public bool $failOnExpire = false;

    public function expireCheckout(string $sessionId): void
    {
        if ($this->failOnExpire) {
            throw new PaymentException('Simulated: session already completed.');
        }

        $this->expired[] = $sessionId;
    }

    /** @var array<string, int> Total refunded per payment reference. */
    public array $refunded = [];

    /** @var list<array{reference: string, amount: int, key: string}> */
    public array $refunds = [];

    public function refund(string $paymentReference, int $amount, string $idempotencyKey): int
    {
        if ($this->failOnRefund) {
            throw new PaymentException('Simulated: refund declined.');
        }

        // Same idempotency key: the provider returns the original refund, not a new one.
        if (! collect($this->refunds)->contains('key', $idempotencyKey)) {
            $this->refunds[] = ['reference' => $paymentReference, 'amount' => $amount, 'key' => $idempotencyKey];
            $this->refunded[$paymentReference] = ($this->refunded[$paymentReference] ?? 0) + $amount;
        }

        return $this->refunded[$paymentReference];
    }

    public bool $failOnRefund = false;

    /** Number of retrieveCheckout() calls, i.e. provider API round trips. */
    public int $retrievals = 0;

    public function retrieveCheckout(string $sessionId): CheckoutResult
    {
        $this->retrievals++;

        if (in_array($sessionId, $this->failRetrieveFor, true)) {
            throw new PaymentException("Simulated: could not retrieve {$sessionId}.");
        }

        return new CheckoutResult(
            sessionId: $sessionId,
            status: $this->statusBySession[$sessionId] ?? $this->status,
            amountTotal: $this->amountTotal,
            email: 'customer@example.com',
            customerName: 'Jane Customer',
            shippingAddress: ['line1' => '1 Test Street', 'city' => 'London', 'postal_code' => 'E1 1AA', 'country' => 'GB'],
            paymentReference: 'pi_test_123',
        );
    }
}
