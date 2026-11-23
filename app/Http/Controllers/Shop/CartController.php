<?php

namespace App\Http\Controllers\Shop;

use App\Cart\Cart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Http\Requests\Shop\UpdateCartLineRequest;
use App\Http\Resources\CartResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function index(): Response
    {
        return Inertia::render('cart/index', [
            'cart' => new CartResource($this->cart),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $product = $request->product();

        $this->cart->add($product, $request->string('colour')->toString(), $request->string('size')->toString(), $request->integer('quantity'));

        return back()->with('success', "{$product->name} was added to your bag.");
    }

    public function update(UpdateCartLineRequest $request, string $line): RedirectResponse
    {
        abort_unless($this->cart->has($line), 404);

        $this->cart->update($line, $request->integer('quantity'));

        return back();
    }

    public function destroy(string $line): RedirectResponse
    {
        abort_unless($this->cart->has($line), 404);

        $this->cart->remove($line);

        return back()->with('success', 'Item removed from your bag.');
    }
}
