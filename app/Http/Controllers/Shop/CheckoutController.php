<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Checkout\CompleteCheckout;
use App\Actions\Checkout\StartCheckout;
use App\Cart\Cart;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Inventory\InsufficientStock;
use App\Models\Order;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    /** Minimum seconds between provider lookups for one session on the success page. */
    private const SYNC_INTERVAL_SECONDS = 5;

    /** Create a pending order and send the customer to the hosted payment page. */
    public function store(Request $request, Cart $cart, StartCheckout $startCheckout): SymfonyResponse
    {
        if ($cart->isEmpty()) {
            return to_route('cart.index')->with('error', 'Your bag is empty.');
        }

        $previousReference = $cart->pendingOrderReference();
        $previous = $previousReference ? Order::pending()->firstWhere('reference', $previousReference) : null;

        try {
            $checkout = $startCheckout->handle($cart, $request->user(), replacing: $previous);
        } catch (InsufficientStock $e) {
            return to_route('cart.index')->with('error', $e->customerMessage());
        } catch (PaymentException $e) {
            report($e);

            return back()->with('error', app()->isLocal()
                ? $e->getMessage()
                : 'We couldn’t start checkout just now. Please try again in a moment.');
        }

        $cart->rememberPendingOrder($checkout->order);

        // Inertia::location performs a full-page redirect to the external Stripe URL.
        return Inertia::location($checkout->redirectUrl);
    }

    /** Where the payment provider sends the customer after paying. */
    public function success(
        Request $request,
        Cart $cart,
        PaymentGateway $gateway,
        CompleteCheckout $completeCheckout,
    ): Response|RedirectResponse {
        $sessionId = $request->string('session_id')->toString();
        abort_if($sessionId === '', 404);

        $order = Order::where('checkout_session_id', $sessionId)->firstOrFail();

        // The webhook is the source of truth, but syncing here means the page is
        // accurate even if the webhook hasn't arrived yet (or isn't set up locally).
        // At most one lookup every few seconds per session, so refreshing the page
        // doesn't turn into a stream of Stripe API calls.
        if (in_array($order->status, OrderStatus::unsettled(), true)
            && Cache::add("checkout-sync:{$sessionId}", true, self::SYNC_INTERVAL_SECONDS)) {
            try {
                $completeCheckout->handle($order, $gateway->retrieveCheckout($sessionId));
            } catch (PaymentException $e) {
                report($e);
            }
        }

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Failed], true)) {
            return to_route('cart.index')->with('error', 'Your payment didn’t go through, so your bag has been kept.');
        }

        // Only empty the bag once money has actually been taken. For delayed payment
        // methods the order can stay pending, and if that payment later fails the
        // customer still has their bag.
        if ($order->isPaid()) {
            $cart->clear();
            $cart->forgetPendingOrder();
        }

        return Inertia::render('checkout/success', [
            'order' => new OrderResource($order->load('items')),
        ]);
    }
}
