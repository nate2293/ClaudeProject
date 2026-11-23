<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    private Category $women;

    private Category $men;

    protected function setUp(): void
    {
        parent::setUp();

        $this->women = Category::factory()->create(['name' => 'Women', 'slug' => 'women']);
        $this->men = Category::factory()->create(['name' => 'Men', 'slug' => 'men']);
    }

    public function test_category_page_only_lists_that_categorys_active_products()
    {
        Product::factory()->for($this->women)->count(2)->create();
        Product::factory()->for($this->women)->inactive()->create();
        Product::factory()->for($this->men)->create();

        $this->get(route('shop.category', $this->women))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('shop/index')
                ->where('category.slug', 'women')
                ->has('products.data', 2)
                ->where('products.meta.total', 2));
    }

    public function test_unknown_category_returns_404()
    {
        $this->get('/shop/kids')->assertNotFound();
    }

    public function test_shop_can_be_filtered_by_type_sale_and_search()
    {
        Product::factory()->for($this->women)->create(['name' => 'Merino Jumper', 'type' => 'Knitwear', 'price' => 8900]);
        Product::factory()->for($this->men)->onSale(9000)->create(['name' => 'Linen Shirt', 'type' => 'Shirts', 'price' => 6000]);

        $this->get(route('shop.index', ['type' => 'Knitwear']))
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.name', 'Merino Jumper'));

        $this->get(route('shop.index', ['sale' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.name', 'Linen Shirt'));

        $this->get(route('shop.index', ['q' => 'merino']))
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('filters.q', 'merino'));
    }

    public function test_products_can_be_sorted_by_price()
    {
        Product::factory()->for($this->women)->create(['price' => 5000]);
        Product::factory()->for($this->women)->create(['price' => 2000]);

        $this->get(route('shop.index', ['sort' => 'price-asc']))
            ->assertInertia(fn (Assert $page) => $page->where('products.data.0.price', 2000));

    }

    public function test_malformed_filters_fall_back_to_defaults_instead_of_redirecting()
    {
        Product::factory()->for($this->women)->create();

        $this->get('/shop?sort=nonsense&sale=maybe&q[]=array&type='.str_repeat('x', 500))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'featured')
                ->where('filters.sale', false)
                ->where('filters.q', null)
                ->where('filters.type', str_repeat('x', 50)));
    }

    public function test_search_treats_wildcard_characters_literally()
    {
        Product::factory()->for($this->women)->create(['name' => '100% Linen Shirt', 'description' => 'Plain.']);
        Product::factory()->for($this->women)->create(['name' => 'Wool Jumper', 'description' => 'Warm.']);
        Product::factory()->for($this->women)->create(['name' => 'Snake_Case Tee', 'description' => 'Odd.']);

        $this->get(route('shop.index', ['q' => '100%']))
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.name', '100% Linen Shirt'));

        $this->get(route('shop.index', ['q' => '%']))->assertInertia(fn (Assert $page) => $page->has('products.data', 1));
        $this->get(route('shop.index', ['q' => '_']))->assertInertia(fn (Assert $page) => $page->has('products.data', 1));
        $this->get(route('shop.index', ['q' => '!']))->assertInertia(fn (Assert $page) => $page->has('products.data', 0));
    }

    public function test_product_page_shows_product_and_related_items()
    {
        $product = Product::factory()->for($this->women)->create();
        Product::factory()->for($this->women)->count(2)->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/show')
                ->where('product.slug', $product->slug)
                ->where('product.category.slug', 'women')
                ->has('related', 2));
    }

    public function test_inactive_product_page_returns_404()
    {
        $product = Product::factory()->for($this->women)->inactive()->create();

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_info_pages_render()
    {
        foreach (['delivery', 'returns', 'size-guide', 'contact', 'about', 'privacy', 'terms'] as $page) {
            $this->get(route("pages.{$page}"))->assertOk()->assertInertia(fn (Assert $p) => $p->component("info/{$page}"));
        }
    }
}
