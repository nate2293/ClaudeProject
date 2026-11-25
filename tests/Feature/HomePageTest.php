<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_storefront_home_page_renders_featured_products()
    {
        $category = Category::factory()->create(['name' => 'Women', 'slug' => 'women']);
        Product::factory()->for($category)->create(['is_featured' => true]);
        Product::factory()->for($category)->create(['is_featured' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('home')
                ->has('featuredProducts', 1)
                ->where('navigation.0.label', 'Women')
                ->where('cart.count', 0));
    }

    public function test_the_collection_figures_come_from_the_catalogue()
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create(['materials' => '100% Extra-fine Merino wool', 'colours' => [['name' => 'Oat', 'hex' => '#d8cbb6'], ['name' => 'Navy', 'hex' => '#28324a']]]);
        Product::factory()->for($category)->create(['materials' => '100% European flax linen', 'colours' => [['name' => 'Sand', 'hex' => '#c8b89a']]]);
        Product::factory()->for($category)->create(['materials' => '100% cashmere', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('collection.pieces', 2)
                ->where('collection.cloths', ['Merino wool', 'Linen'])
                ->has('collection.names', 2)
                ->where('collection.freeDeliveryOver', config('shop.shipping.free_threshold'))
                ->where('collection.returnDays', 30));
    }
}
