<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\StockLevel;
use Illuminate\Console\Command;

/**
 * Shows and sets a product's stock until there is an admin screen for it.
 *
 *   php artisan shop:stock merino-crew-neck-jumper                      # show
 *   php artisan shop:stock merino-crew-neck-jumper 12                   # set every variant to 12
 *   php artisan shop:stock merino-crew-neck-jumper 5 --colour=Oat --size=M --add   # restock one variant
 */
class ManageStock extends Command
{
    protected $signature = 'shop:stock
        {product : Product slug}
        {quantity? : Units on hand to set (or add, with --add)}
        {--colour= : Only this colour}
        {--size= : Only this size}
        {--add : Add the quantity to what is on hand instead of replacing it}';

    protected $description = 'Show or update stock for a product\'s variants';

    public function handle(): int
    {
        $product = Product::firstWhere('slug', $this->argument('product'));

        if (! $product) {
            $this->error("No product with slug \"{$this->argument('product')}\".");

            return self::FAILURE;
        }

        $colours = array_column($product->colours, 'name');
        $colour = $this->option('colour');
        $size = $this->option('size');

        if ($colour !== null && ! in_array($colour, $colours, true)) {
            $this->error('Colour must be one of: '.implode(', ', $colours));

            return self::FAILURE;
        }

        if ($size !== null && ! in_array($size, $product->sizes, true)) {
            $this->error('Size must be one of: '.implode(', ', $product->sizes));

            return self::FAILURE;
        }

        $quantity = $this->argument('quantity');

        if ($quantity !== null) {
            if (filter_var($quantity, FILTER_VALIDATE_INT) === false || (int) $quantity < 0) {
                $this->error('Quantity must be a whole number of 0 or more.');

                return self::FAILURE;
            }

            foreach ($colour !== null ? [$colour] : $colours as $c) {
                foreach ($size !== null ? [$size] : $product->sizes as $s) {
                    $level = StockLevel::firstOrCreate(['product_id' => $product->id, 'colour' => $c, 'size' => $s]);

                    // Atomic, so a sale happening at the same moment isn't overwritten.
                    $this->option('add')
                        ? $level->increment('on_hand', (int) $quantity)
                        : $level->update(['on_hand' => (int) $quantity]);
                }
            }
        }

        $this->info($product->name);
        $this->table(
            ['Colour', 'Size', 'On hand', 'Reserved', 'Available'],
            $product->stockLevels()->orderBy('colour')->orderBy('id')->get()
                ->map(fn (StockLevel $level) => [$level->colour, $level->size, $level->on_hand, $level->reserved, $level->available()]),
        );

        return self::SUCCESS;
    }
}
