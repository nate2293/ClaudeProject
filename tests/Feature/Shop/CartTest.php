<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function addToCart(Product $product, array $overrides = [])
    {
        return $this->post(route('cart.lines.store'), [
            'product_id' => $product->id,
            'colour' => 'Black',
            'size' => 'M',
            'quantity' => 1,
            ...$overrides,
        ]);
    }

    public function test_a_product_can_be_added_to_the_bag()
    {
        $product = Product::factory()->create(['price' => 3000]);

        $this->addToCart($product, ['quantity' => 2])->assertRedirect()->assertSessionHas('success');

        $this->get(route('cart.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('cart/index')
                ->has('cart.lines', 1)
                ->where('cart.lines.0.quantity', 2)
                ->where('cart.subtotal', 6000)
                ->where('cart.shipping', 495)
                ->where('cart.total', 6495)
                ->where('cart.count', 2));
    }

    public function test_adding_the_same_options_merges_lines_and_caps_quantity()
    {
        $product = Product::factory()->stocked(50)->create();

        $this->addToCart($product, ['quantity' => 8]);
        $this->addToCart($product, ['quantity' => 8]);
        $this->addToCart($product, ['size' => 'L']);

        $this->get(route('cart.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('cart.lines', 2)
                ->where('cart.lines.0.quantity', config('shop.max_quantity_per_line')));
    }

    public function test_free_shipping_applies_over_the_threshold()
    {
        $product = Product::factory()->create(['price' => 8000]);

        $this->addToCart($product);

        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page->where('cart.shipping', 0)->where('cart.total', 8000));
    }

    public function test_unavailable_options_are_rejected()
    {
        $product = Product::factory()->create();

        $this->addToCart($product, ['colour' => 'Purple', 'size' => 'XXL'])->assertSessionHasErrors(['colour', 'size']);
        $this->addToCart($product, ['quantity' => 0])->assertSessionHasErrors('quantity');
        $this->addToCart(Product::factory()->inactive()->create())->assertSessionHasErrors('product_id');
    }

    public function test_lines_can_be_updated_and_removed()
    {
        $product = Product::factory()->create();
        $this->addToCart($product);

        $key = $this->get(route('cart.index'))->viewData('page')['props']['cart']['lines'][0]['key'];

        $this->patch(route('cart.lines.update', $key), ['quantity' => 3])->assertRedirect();
        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page->where('cart.lines.0.quantity', 3));

        $this->delete(route('cart.lines.destroy', $key))->assertRedirect();
        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page->has('cart.lines', 0));

        $this->patch(route('cart.lines.update', 'missing'), ['quantity' => 1])->assertNotFound();
    }

    public function test_cart_always_uses_current_prices_and_drops_deactivated_products()
    {
        $kept = Product::factory()->create(['price' => 2000]);
        $removed = Product::factory()->create();
        $this->addToCart($kept);
        $this->addToCart($removed);

        $kept->update(['price' => 2500]);
        $removed->update(['is_active' => false]);

        $this->get(route('cart.index'))
            ->assertInertia(fn (Assert $page) => $page->has('cart.lines', 1)->where('cart.subtotal', 2500)->where('cart.count', 1));
    }

    public function test_header_count_ignores_products_that_are_no_longer_for_sale()
    {
        $product = Product::factory()->create();
        $this->addToCart($product, ['quantity' => 2]);

        $product->update(['is_active' => false]);

        // Any page, not just the bag: the shared header count must agree with the bag.
        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('cart.count', 0));
        $this->assertSame([], session('cart.lines'));
    }
}
