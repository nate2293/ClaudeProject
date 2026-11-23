<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

/**
 * Caches added for traffic: each must stay correct when the data changes.
 */
class CachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_is_cached_and_refreshed_when_a_category_changes()
    {
        $women = Category::factory()->create(['name' => 'Women', 'slug' => 'women', 'position' => 1]);
        $this->assertSame(['Women'], array_column(Category::navigation(), 'name'));

        // Cached: a write that bypasses the model isn't seen...
        DB::table('categories')->where('id', $women->id)->update(['name' => 'Changed directly']);
        $this->assertSame(['Women'], array_column(Category::navigation(), 'name'));

        // ...but saving, adding or deleting a category through the model refreshes it.
        $women->refresh()->update(['name' => 'Womenswear']);
        $men = Category::factory()->create(['name' => 'Men', 'slug' => 'men', 'position' => 2]);
        $this->assertSame(['Womenswear', 'Men'], array_column(Category::navigation(), 'name'));

        $men->delete();
        $this->assertSame(['Womenswear'], array_column(Category::navigation(), 'name'));

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('navigation.0.label', 'Womenswear')
            ->where('navigation.0.href', route('shop.category', 'women')));
    }

    public function test_related_products_prefer_the_same_type_and_drop_deactivated_ones_immediately()
    {
        $women = Category::factory()->create();
        $men = Category::factory()->create();
        $jumper = Product::factory()->for($women)->create(['type' => 'Knitwear']);
        $otherJumper = Product::factory()->for($women)->create(['type' => 'Knitwear', 'is_featured' => false]);
        $featuredShirt = Product::factory()->for($women)->create(['type' => 'Shirts', 'is_featured' => true]);
        Product::factory()->for($women)->create(['type' => 'Knitwear', 'is_active' => false]);
        Product::factory()->for($men)->create(['type' => 'Knitwear']);

        $this->get(route('products.show', $jumper))->assertInertia(fn (Assert $page) => $page
            ->has('related', 2)
            ->where('related.0.id', $otherJumper->id)
            ->where('related.1.id', $featuredShirt->id));

        $otherJumper->update(['is_active' => false]);

        $this->get(route('products.show', $jumper))->assertInertia(fn (Assert $page) => $page
            ->has('related', 1)
            ->where('related.0.id', $featuredShirt->id));
    }

    public function test_success_page_refreshes_call_the_provider_at_most_every_few_seconds()
    {
        // Redis expires keys in real time, so time travel needs the array store.
        Cache::setDefaultDriver('array');
        $gateway = new FakePaymentGateway;
        $gateway->status = CheckoutStatus::Pending;
        $this->app->instance(PaymentGateway::class, $gateway);
        $order = Order::create(['currency' => 'gbp', 'subtotal' => 5000, 'shipping' => 495, 'total' => 5495, 'checkout_session_id' => 'cs_test_1']);

        $this->get(route('checkout.success', ['session_id' => 'cs_test_1']))->assertOk();
        $this->get(route('checkout.success', ['session_id' => 'cs_test_1']))->assertOk();
        $this->assertSame(1, $gateway->retrievals);

        $this->travel(6)->seconds();
        $gateway->status = CheckoutStatus::Paid;
        $this->get(route('checkout.success', ['session_id' => 'cs_test_1']))->assertOk();

        $this->assertSame(2, $gateway->retrievals);
        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }
}
