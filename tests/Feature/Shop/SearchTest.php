<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Search\DatabaseProductSearch;
use App\Search\ProductSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The search engine path (Laravel Scout). Meilisearch itself isn't needed: the
 * collection engine stands in for it, and the database search is covered by
 * CatalogueTest.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private Category $women;

    protected function setUp(): void
    {
        parent::setUp();

        $this->women = Category::factory()->create(['name' => 'Women', 'slug' => 'women']);
    }

    private function useEngine(string $driver): void
    {
        config(['scout.driver' => $driver]);
        $this->app->forgetScopedInstances();
    }

    public function test_the_database_is_used_unless_a_search_engine_is_configured()
    {
        $this->assertInstanceOf(DatabaseProductSearch::class, app(ProductSearch::class));
    }

    public function test_the_engine_also_matches_colours_and_category_and_keeps_shop_filters()
    {
        $this->useEngine('collection');
        $oat = Product::factory()->for($this->women)->create([
            'name' => 'Crew Neck Jumper', 'type' => 'Knitwear', 'description' => 'Soft.',
            'colours' => [['name' => 'Oat', 'hex' => '#d8cbb6']],
        ]);
        Product::factory()->for($this->women)->create(['name' => 'Linen Shirt', 'type' => 'Shirts', 'description' => 'Crisp.']);
        Product::factory()->for($this->women)->inactive()->create(['name' => 'Oat Scarf', 'type' => 'Accessories']);

        $this->get(route('shop.index', ['q' => 'oat']))->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $oat->id));

        $this->get(route('shop.index', ['q' => 'women', 'type' => 'Shirts']))->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Linen Shirt')
            ->where('types', ['Knitwear', 'Shirts']));
    }

    public function test_ranked_results_are_shown_best_match_first_unless_another_sort_is_chosen()
    {
        $cheap = Product::factory()->for($this->women)->create(['price' => 2000]);
        $dear = Product::factory()->for($this->women)->create(['price' => 9000]);
        $mid = Product::factory()->for($this->women)->create(['price' => 5000]);

        // An engine that ranks $dear, then $cheap, then $mid.
        $this->app->instance(ProductSearch::class, new class([$dear->id, $cheap->id, $mid->id]) implements ProductSearch
        {
            public function __construct(private array $ranked) {}

            public function constrain(Builder $query, string $term): ?array
            {
                $query->whereKey($this->ranked);

                return $this->ranked;
            }
        });

        $this->get(route('shop.index', ['q' => 'anything']))->assertInertia(fn (Assert $page) => $page
            ->where('products.data', fn ($products) => collect($products)->pluck('id')->all() === [$dear->id, $cheap->id, $mid->id]));

        $this->get(route('shop.index', ['q' => 'anything', 'sort' => 'price-asc']))->assertInertia(fn (Assert $page) => $page
            ->where('products.data', fn ($products) => collect($products)->pluck('id')->all() === [$cheap->id, $mid->id, $dear->id]));
    }

    public function test_search_falls_back_to_the_database_when_the_engine_is_down()
    {
        Product::factory()->for($this->women)->create(['name' => 'Merino Jumper']);
        Log::spy();
        $this->useEngine('meilisearch');
        config(['scout.meilisearch.host' => 'http://127.0.0.1:1']); // Nothing listens here.

        $this->get(route('shop.index', ['q' => 'merino']))->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Merino Jumper'));

        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_the_search_document_holds_what_shoppers_search_for()
    {
        $product = Product::factory()->for($this->women)->create([
            'name' => 'Merino Jumper', 'colours' => [['name' => 'Oat', 'hex' => '#000'], ['name' => 'Navy', 'hex' => '#000']],
            'materials' => '100% merino wool',
        ]);

        $document = $product->load('category')->toSearchableArray();

        $this->assertSame(['Oat', 'Navy'], $document['colours']);
        $this->assertSame('Women', $document['category']);
        $this->assertSame('100% merino wool', $document['materials']);
        $this->assertTrue($product->shouldBeSearchable());
        $this->assertFalse($product->forceFill(['is_active' => false])->shouldBeSearchable());
    }
}
