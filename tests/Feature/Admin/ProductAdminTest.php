<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\StockLevelsRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create()->forceFill(['is_admin' => true]));
        Storage::fake('public');
    }

    public function test_a_product_is_created_with_a_photo_prices_in_pounds_and_a_stock_level_per_variant()
    {
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Linen Shirt', 'slug' => 'linen-shirt', 'category_id' => $category->id, 'type' => 'Shirts',
                'description' => 'Crisp linen.', 'price' => '59.50', 'compare_at_price' => '75', 'is_active' => true,
                'colours' => [['name' => 'White', 'hex' => '#ffffff'], ['name' => 'Sky', 'hex' => '#a6c8e0']],
                'sizes' => ['S', 'M', 'L'], 'swatch' => '#eeeeee',
                'image' => UploadedFile::fake()->image('shirt.jpg', 1200, 1600),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::firstWhere('slug', 'linen-shirt');
        $this->assertSame(5950, $product->price);
        $this->assertSame(7500, $product->compare_at_price);
        $this->assertSame(6, $product->stockLevels()->count());
        $this->assertSame(0, (int) $product->stockLevels()->sum('on_hand'));

        Storage::disk('public')->assertExists($product->image);
        $this->assertStringStartsWith('products/', $product->image);
        $this->assertStringContainsString('/storage/products/', Media::url($product->image));
    }

    public function test_the_was_price_must_be_higher_than_the_price()
    {
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Tee', 'slug' => 'tee', 'category_id' => $category->id, 'type' => 'Tops', 'description' => 'A tee.',
                'price' => '20', 'compare_at_price' => '15', 'colours' => [['name' => 'Black', 'hex' => '#000000']], 'sizes' => ['M'], 'swatch' => '#eee',
            ])
            ->call('create')
            ->assertHasFormErrors(['compare_at_price']);
    }

    public function test_changing_sizes_adds_new_variants_and_drops_removed_ones_not_in_checkouts()
    {
        $product = Product::factory()->create(['sizes' => ['S', 'M']]); // Black/White × S/M, 10 each
        StockLevel::where(['product_id' => $product->id, 'colour' => 'White', 'size' => 'S'])->update(['reserved' => 1]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['sizes' => ['M', 'L']])
            ->call('save')
            ->assertHasNoFormErrors();

        $variants = $product->stockLevels()->get()->map(fn ($l) => "{$l->colour}/{$l->size}={$l->on_hand}")->sort()->values()->all();
        $this->assertSame(['Black/L=0', 'Black/M=10', 'White/L=0', 'White/M=10', 'White/S=10'], $variants, 'White/S is kept: a checkout holds a unit.');
    }

    public function test_deliveries_are_added_to_stock()
    {
        $product = Product::factory()->stocked(3)->create();
        $level = $product->stockLevels()->first();

        Livewire::test(StockLevelsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callTableAction('receive', $level, data: ['units' => 12]);

        $this->assertSame(15, $level->refresh()->on_hand);
    }
}
