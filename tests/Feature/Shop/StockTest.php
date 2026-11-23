<?php

namespace Tests\Feature\Shop;

use App\Actions\Checkout\CompleteCheckout;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockLevel;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

/**
 * Stock is reserved when checkout starts, sold when the order is paid and
 * released when it's cancelled or fails, and never sold twice.
 */
class StockTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    private function level(Product $product, string $colour = 'Black', string $size = 'M'): StockLevel
    {
        return StockLevel::where(['product_id' => $product->id, 'colour' => $colour, 'size' => $size])->sole();
    }

    private function addToBag(Product $product, int $quantity = 1, string $colour = 'Black', string $size = 'M')
    {
        return $this->post(route('cart.lines.store'), ['product_id' => $product->id, 'colour' => $colour, 'size' => $size, 'quantity' => $quantity]);
    }

    /** Act as a different shopper: a fresh session with an empty bag. */
    private function newShopper(): void
    {
        $this->flushSession();
    }

    private function settle(Order $order, CheckoutStatus $status): void
    {
        $this->gateway->status = $status;
        app(CompleteCheckout::class)->handle($order, $this->gateway->retrieveCheckout($order->checkout_session_id));
    }

    // Reserve, sell, release -----------------------------------------------------

    public function test_starting_checkout_reserves_stock_and_paying_sells_it()
    {
        $product = Product::factory()->stocked(5)->create();
        $this->addToBag($product, 2);

        $this->post(route('checkout.store'));
        $order = Order::sole();

        $this->assertSame([5, 2, 3], [$this->level($product)->on_hand, $this->level($product)->reserved, $this->level($product)->available()]);
        $this->assertSame($this->level($product)->id, $order->items->sole()->stock_level_id);

        $this->settle($order, CheckoutStatus::Paid);

        $this->assertSame([3, 0, 3], [$this->level($product)->on_hand, $this->level($product)->reserved, $this->level($product)->available()]);
    }

    public function test_expired_and_failed_checkouts_release_their_stock()
    {
        $product = Product::factory()->stocked(5)->create();

        $this->addToBag($product, 2);
        $this->post(route('checkout.store'));
        $expired = Order::latest('id')->first();

        $this->newShopper();
        $this->addToBag($product, 1);
        $this->post(route('checkout.store'));
        $failed = Order::latest('id')->first();

        $this->assertSame(3, $this->level($product)->reserved);

        $this->settle($expired, CheckoutStatus::Expired);
        $this->assertSame(1, $this->level($product)->reserved);

        $this->settle($failed, CheckoutStatus::Processing); // Delayed payment keeps its hold...
        $this->assertSame(1, $this->level($product)->reserved);
        app(CompleteCheckout::class)->fail($failed);       // ...until it fails.

        $this->assertSame([5, 0], [$this->level($product)->on_hand, $this->level($product)->reserved]);
    }

    public function test_repeated_notifications_never_count_stock_twice()
    {
        $product = Product::factory()->stocked(5)->create();
        $this->addToBag($product, 2);
        $this->post(route('checkout.store'));
        $order = Order::sole();

        $this->settle($order, CheckoutStatus::Paid);
        $this->settle($order, CheckoutStatus::Paid);
        $this->settle($order, CheckoutStatus::Expired);
        app(CompleteCheckout::class)->fail($order);

        $this->assertSame([3, 0], [$this->level($product)->on_hand, $this->level($product)->reserved]);
    }

    public function test_a_failed_payment_page_releases_the_reservation()
    {
        $product = Product::factory()->stocked(5)->create();
        $this->addToBag($product, 2);
        $this->gateway->failOnCreate = true;

        $this->post(route('checkout.store'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $this->level($product)->reserved);
    }

    // The last unit --------------------------------------------------------------

    public function test_two_shoppers_cannot_both_buy_the_last_unit()
    {
        $product = Product::factory()->stocked(1)->create(['name' => 'Merino Jumper']);

        $this->addToBag($product);
        $this->newShopper();
        $this->addToBag($product)->assertSessionHasNoErrors(); // Both bags hold it; nobody has paid.

        $this->post(route('checkout.store'));
        $this->assertSame(1, $this->level($product)->reserved);

        $this->newShopper();
        $this->addToBag($product)->assertSessionHasErrors('size'); // Now it's held, it shows as sold out.
    }

    public function test_checkout_is_refused_when_the_bag_outgrew_the_stock_and_nothing_is_reserved()
    {
        $plenty = Product::factory()->stocked(5)->create();
        $scarce = Product::factory()->stocked(3)->create(['name' => 'Linen Shirt']);
        $this->addToBag($plenty, 2);
        $this->addToBag($scarce, 3);
        $this->level($scarce)->update(['on_hand' => 1]); // Sold elsewhere meanwhile.

        $this->post(route('checkout.store'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Linen Shirt (Black, M) has only 1 left'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $this->level($plenty)->reserved, 'All or nothing: the other line is not held.');

        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page
            ->where('cart.hasStockIssues', true)
            ->where('cart.lines.1.available', 1));
    }

    public function test_a_shopper_returning_from_the_payment_page_can_restart_checkout_for_the_last_unit()
    {
        $product = Product::factory()->stocked(1)->create();
        $this->addToBag($product);
        $this->post(route('checkout.store'));
        $first = Order::sole();

        // Their own hold counts as theirs: the bag isn't flagged and checkout works again.
        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page
            ->where('cart.hasStockIssues', false)
            ->where('cart.lines.0.available', 1));

        $this->post(route('checkout.store'))->assertSessionMissing('error');

        $this->assertSame(OrderStatus::Cancelled, $first->refresh()->status);
        $this->assertSame(1, $this->level($product)->reserved);
    }

    public function test_paying_for_more_than_is_on_hand_is_flagged()
    {
        Log::spy();
        $product = Product::factory()->stocked(2)->create();
        $this->addToBag($product, 2);
        $this->post(route('checkout.store'));
        $this->level($product)->update(['on_hand' => 1]); // Miscounted by hand while the payment was in flight.

        $this->settle(Order::sole(), CheckoutStatus::Paid);

        $this->assertSame(-1, $this->level($product)->on_hand);
        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message) => str_contains($message, 'more units than are on hand'));
    }

    // Bag and product pages --------------------------------------------------------

    public function test_the_bag_only_accepts_what_is_in_stock()
    {
        $product = Product::factory()->stocked(3)->create();

        $this->addToBag($product, 4)->assertSessionHasErrors(['quantity' => 'Only 3 left in this size.']);
        $this->addToBag($product, 2)->assertSessionHasNoErrors();
        $this->addToBag($product, 2)->assertSessionHasErrors(['quantity' => 'Only 3 left, and you already have 2 in your bag.']);

        $this->level($product)->update(['on_hand' => 0]);
        $this->addToBag($product, 1, size: 'M')->assertSessionHasErrors('size');
    }

    public function test_bag_quantities_can_be_lowered_but_only_raised_within_stock()
    {
        $product = Product::factory()->stocked(4)->create();
        $this->addToBag($product, 3);
        $key = array_key_first(session('cart.lines'));
        $this->level($product)->update(['on_hand' => 2]);

        $this->patch(route('cart.lines.update', $key), ['quantity' => 4])->assertSessionHasErrors(['quantity' => 'Only 2 left.']);
        $this->patch(route('cart.lines.update', $key), ['quantity' => 2])->assertSessionHasNoErrors();
        $this->assertSame(2, session('cart.lines')[$key]['quantity']);
    }

    public function test_pages_show_stock_without_revealing_exact_counts()
    {
        $product = Product::factory()->stocked(40)->create();
        $this->level($product, 'White', 'L')->update(['on_hand' => 0]);
        $soldOut = Product::factory()->for($product->category)->soldOut()->create();

        $this->get(route('products.show', $product))->assertInertia(fn (Assert $page) => $page
            ->has('product.stock', 6)
            ->where('product.stock', fn ($stock) => collect($stock)->firstWhere('size', 'M')['available'] === config('shop.max_quantity_per_line')
                && collect($stock)->first(fn ($s) => $s['colour'] === 'White' && $s['size'] === 'L')['available'] === 0));

        $this->get(route('shop.category', $product->category))->assertInertia(fn (Assert $page) => $page
            ->where('products.data', fn ($products) => collect($products)->firstWhere('id', $soldOut->id)['inStock'] === false
                && collect($products)->firstWhere('id', $product->id)['inStock'] === true));
    }

    public function test_stock_can_be_set_and_restocked_from_the_command_line()
    {
        $product = Product::factory()->stocked(1)->create(['name' => 'Merino Jumper', 'slug' => 'merino-jumper']);

        $this->artisan('shop:stock', ['product' => 'merino-jumper', 'quantity' => 6])->assertSuccessful();
        $this->artisan('shop:stock', ['product' => 'merino-jumper', 'quantity' => 4, '--colour' => 'Black', '--size' => 'M', '--add' => true])
            ->expectsOutputToContain('Merino')
            ->assertSuccessful();

        $this->assertSame(10, $this->level($product)->on_hand);
        $this->assertSame(6, $this->level($product, 'White', 'S')->on_hand);

        $this->artisan('shop:stock', ['product' => 'merino-jumper', 'quantity' => 1, '--size' => 'XXL'])->assertFailed();
        $this->artisan('shop:stock', ['product' => 'nope'])->assertFailed();
    }
}
