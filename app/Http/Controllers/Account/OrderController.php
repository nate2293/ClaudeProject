<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountOrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The customer's order history and order details. */
class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->forCustomer($request->user())
            ->visibleToCustomer()
            ->with('items.product')
            ->latest()
            ->paginate(10);

        return Inertia::render('account/orders/index', [
            'orders' => AccountOrderResource::collection($orders),
        ]);
    }

    public function show(Request $request, string $reference): Response
    {
        // Not found (rather than forbidden) for other people's orders, so references can't be probed.
        $order = Order::query()
            ->forCustomer($request->user())
            ->visibleToCustomer()
            ->where('reference', $reference)
            ->with('items.product')
            ->firstOrFail();

        return Inertia::render('account/orders/show', [
            'order' => new AccountOrderResource($order),
        ]);
    }
}
