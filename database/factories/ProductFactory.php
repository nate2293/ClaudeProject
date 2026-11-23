<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(['Knitwear', 'Shirts', 'Outerwear']),
            'description' => fake()->paragraph(),
            'materials' => '100% cotton',
            'price' => fake()->numberBetween(20, 200) * 100,
            'colours' => [['name' => 'Black', 'hex' => '#1f1f1f'], ['name' => 'White', 'hex' => '#f6f4ef']],
            'sizes' => ['S', 'M', 'L'],
            'swatch' => fake()->hexColor(),
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    /** Every variant gets a stock level (10 units unless a state says otherwise). */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            foreach ($product->colours as $colour) {
                foreach ($product->sizes as $size) {
                    $product->stockLevels()->create(['colour' => $colour['name'], 'size' => $size, 'on_hand' => 10]);
                }
            }
        });
    }

    /** Set every variant's stock to the given number of units. */
    public function stocked(int $units): static
    {
        return $this->afterCreating(fn (Product $product) => $product->stockLevels()->update(['on_hand' => $units]));
    }

    public function soldOut(): static
    {
        return $this->stocked(0);
    }

    public function onSale(int $compareAt): static
    {
        return $this->state(fn () => ['compare_at_price' => $compareAt, 'badge' => 'sale']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
