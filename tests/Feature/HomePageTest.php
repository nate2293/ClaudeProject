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

    public function test_the_storefront_home_page_renders_categories_and_featured_products()
    {
        $category = Category::factory()->create(['name' => 'Women', 'slug' => 'women']);
        Product::factory()->for($category)->create(['is_featured' => true]);
        Product::factory()->for($category)->create(['is_featured' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('home')
                ->has('categories', 1)
                ->has('featuredProducts', 1)
                ->where('navigation.0.label', 'Women')
                ->where('cart.count', 0));
    }
}
