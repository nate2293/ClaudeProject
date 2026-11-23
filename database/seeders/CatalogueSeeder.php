<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the sample Loom & Lane catalogue: three departments with realistic products.
 * Safe to re-run: records are matched on slug and updated in place.
 */
class CatalogueSeeder extends Seeder
{
    private const APPAREL_SIZES = ['XS', 'S', 'M', 'L', 'XL'];

    private const ONE_SIZE = ['One size'];

    public function run(): void
    {
        foreach ($this->catalogue() as $position => $department) {
            $category = Category::updateOrCreate(
                ['slug' => $department['slug']],
                [
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'swatch' => $department['swatch'],
                    'position' => $position,
                ],
            );

            foreach ($department['products'] as $product) {
                $model = $category->products()->updateOrCreate(
                    ['slug' => Str::slug($product['name'])],
                    [
                        'sizes' => $department['sizes'],
                        'is_active' => true,
                        ...$product,
                    ],
                );

                $this->seedStock($model);
            }
        }
    }

    /**
     * Sample stock for every variant, so the shop shows a realistic mix of plenty,
     * "only a few left" and sold out. Existing levels are left alone on re-runs,
     * so seeding never resets real stock counts.
     */
    private function seedStock(Product $product): void
    {
        foreach ($product->colours as $colour) {
            foreach ($product->sizes as $size) {
                // Deterministic 0-14 per variant; roughly 1 in 8 sold out.
                $units = crc32("{$product->slug}|{$colour['name']}|{$size}") % 15;

                $product->stockLevels()->firstOrCreate(
                    ['colour' => $colour['name'], 'size' => $size],
                    ['on_hand' => $units < 2 ? 0 : $units],
                );
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            [
                'name' => 'Women',
                'slug' => 'women',
                'description' => 'Knitwear, tailoring and everyday staples.',
                'swatch' => '#d9c7b4',
                'sizes' => self::APPAREL_SIZES,
                'products' => [
                    $this->product('Merino Crew Neck Jumper', 'Knitwear', 8900, '#e4d9c8', [['Oat', '#d8cbb6'], ['Navy', '#28324a'], ['Forest', '#3f5446']],
                        'A lightweight jumper knitted from extra-fine merino. Breathable, naturally temperature-regulating and soft enough to wear next to the skin.',
                        '100% extra-fine merino wool', badge: 'new', featured: true),
                    $this->product('Pleated Midi Skirt', 'Skirts', 7900, '#c9c7ad', [['Olive', '#6b6b45'], ['Black', '#1f1f1f']],
                        'A fluid midi skirt with knife pleats and a concealed side zip. Falls just below the knee and moves beautifully.',
                        '100% recycled polyester', compareAt: 9500, badge: 'sale', featured: true),
                    $this->product('Wool Blend Overcoat', 'Outerwear', 18500, '#cdb79a', [['Camel', '#b08a5f'], ['Charcoal', '#3b3b3b']],
                        'A relaxed single-breasted coat in a dense wool blend, with dropped shoulders and deep patch pockets.',
                        '70% wool, 30% recycled polyamide', compareAt: 23000, badge: 'sale', featured: true),
                    $this->product('Relaxed Linen Shirt', 'Shirts', 6500, '#dfe3e2', [['White', '#f4f1ea'], ['Sky', '#a9bfd1']],
                        'An oversized shirt in garment-washed European linen that gets softer with every wear.',
                        '100% European flax linen', featured: true),
                    $this->product('Ribbed Cardigan', 'Knitwear', 9500, '#e2cfc0', [['Ecru', '#efe6d6'], ['Rose', '#c99a8f']],
                        'A cropped cardigan in a chunky rib with horn-effect buttons. Layer it over dresses or wear it buttoned up on its own.',
                        '60% organic cotton, 40% wool', badge: 'new'),
                    $this->product('Wide Leg Trousers', 'Trousers', 8500, '#cfc6ba', [['Stone', '#c2b8a8'], ['Black', '#1f1f1f']],
                        'High-waisted trousers with front pleats and a fluid wide leg, cut from a crease-resistant twill.',
                        '68% TENCEL™ lyocell, 32% linen'),
                    $this->product('Organic Cotton Tee', 'Essentials', 2800, '#ece6dc', [['White', '#f6f4ef'], ['Black', '#1f1f1f'], ['Clay', '#b9765a']],
                        'Our everyday tee in heavyweight organic cotton jersey with a slightly boxy fit.',
                        '100% organic cotton', badge: 'bestseller'),
                    $this->product('Quilted Liner Jacket', 'Outerwear', 11500, '#b9b6a3', [['Khaki', '#7a7656'], ['Navy', '#26303f']],
                        'A lightweight diamond-quilted jacket that works on its own or layered under a coat.',
                        'Shell: 100% recycled nylon. Fill: recycled polyester'),
                ],
            ],
            [
                'name' => 'Men',
                'slug' => 'men',
                'description' => 'Overshirts, chinos and layering essentials.',
                'swatch' => '#9aa39a',
                'sizes' => self::APPAREL_SIZES,
                'products' => [
                    $this->product('Waxed Cotton Field Jacket', 'Outerwear', 16500, '#aeb09a', [['Olive', '#4d5138'], ['Navy', '#26303f']],
                        'A four-pocket field jacket in British waxed cotton with a corduroy collar. Shower-proof and ages beautifully.',
                        '100% waxed cotton', featured: true),
                    $this->product('Straight Leg Selvedge Jeans', 'Denim', 11000, '#b9c2cf', [['Indigo', '#2c3a5a'], ['Washed', '#6e83a3']],
                        'Raw selvedge denim woven on vintage shuttle looms, cut with a classic straight leg.',
                        '100% cotton selvedge denim, 13.5oz', featured: true),
                    $this->product('Brushed Flannel Overshirt', 'Shirts', 7500, '#c7b29c', [['Tobacco', '#8a6545'], ['Grey Check', '#8d8f91']],
                        'A heavyweight overshirt in brushed cotton flannel with two chest pockets. Your go-to layer from autumn to spring.',
                        '100% organic cotton flannel', badge: 'new'),
                    $this->product('Lambswool Crew Neck', 'Knitwear', 8500, '#b7a58e', [['Oatmeal', '#cbbba1'], ['Bottle Green', '#2f4a3b']],
                        'A classic crew neck knitted in Scotland from soft, warm lambswool.',
                        '100% lambswool'),
                    $this->product('Oxford Button-Down Shirt', 'Shirts', 6000, '#d5dde4', [['Blue', '#9fb4cc'], ['White', '#f4f1ea']],
                        'A wardrobe staple in a sturdy Oxford weave with a soft roll button-down collar.',
                        '100% organic cotton', badge: 'bestseller'),
                    $this->product('Tapered Chinos', 'Trousers', 7000, '#cbbf9f', [['Sand', '#c4b48c'], ['Navy', '#28324a']],
                        'Mid-rise chinos with a gently tapered leg, in a cotton twill with a touch of stretch.',
                        '98% organic cotton, 2% elastane', compareAt: 8500, badge: 'sale'),
                    $this->product('Heavyweight Pocket Tee', 'Essentials', 3200, '#dcd7cf', [['White', '#f6f4ef'], ['Charcoal', '#3b3b3b'], ['Sage', '#9aa88f']],
                        'A boxy tee in 240gsm organic cotton with a single chest pocket.',
                        '100% organic cotton'),
                    $this->product('Wool Car Coat', 'Outerwear', 21000, '#8f8478', [['Charcoal', '#3b3b3b'], ['Camel', '#b08a5f']],
                        'A clean-lined car coat in Italian wool with a concealed placket and half lining.',
                        '80% wool, 20% polyamide', badge: 'new'),
                ],
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Scarves, beanies and leather goods.',
                'swatch' => '#c48a6a',
                'sizes' => self::ONE_SIZE,
                'products' => [
                    $this->product('Cashmere Ribbed Scarf', 'Scarves', 7500, '#d8b9a5', [['Rust', '#a4552f'], ['Grey Marl', '#9b9a97']],
                        'A generously long scarf in a fine rib, knitted from pure Mongolian cashmere.',
                        '100% cashmere', badge: 'new', featured: true),
                    $this->product('Merino Beanie', 'Hats', 3500, '#c9b9a6', [['Oat', '#d8cbb6'], ['Navy', '#28324a'], ['Rust', '#a4552f']],
                        'A close-fitting rib beanie with a turn-up cuff, in soft extra-fine merino.',
                        '100% extra-fine merino wool', badge: 'bestseller'),
                    $this->product('Leather Card Holder', 'Leather Goods', 4500, '#a47e5f', [['Tan', '#a0673d'], ['Black', '#1f1f1f']],
                        'A slim four-slot card holder in vegetable-tanned leather, hand-finished in Spain.',
                        'Vegetable-tanned leather'),
                    $this->product('Canvas Tote Bag', 'Bags', 4000, '#d7cdb9', [['Natural', '#e3d9c4'], ['Black', '#1f1f1f']],
                        'A sturdy tote in heavyweight organic canvas with leather handles and an inside pocket.',
                        '100% organic cotton canvas, leather trims'),
                    $this->product('Leather Belt', 'Leather Goods', 5500, '#8d6a50', [['Brown', '#6b4a33'], ['Black', '#1f1f1f']],
                        'A 3cm belt in full-grain leather with a solid brass buckle.',
                        'Full-grain leather, solid brass', compareAt: 6500, badge: 'sale'),
                    $this->product('Lambswool Gloves', 'Gloves', 3000, '#b8aa98', [['Charcoal', '#3b3b3b'], ['Camel', '#b08a5f']],
                        'Warm knitted gloves with touchscreen-compatible fingertips.',
                        '90% lambswool, 10% conductive yarn'),
                ],
            ],
        ];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $colours
     * @return array<string, mixed>
     */
    private function product(
        string $name,
        string $type,
        int $price,
        string $swatch,
        array $colours,
        string $description,
        string $materials,
        ?int $compareAt = null,
        ?string $badge = null,
        bool $featured = false,
    ): array {
        return [
            'name' => $name,
            'type' => $type,
            'price' => $price,
            'compare_at_price' => $compareAt,
            'badge' => $badge,
            'swatch' => $swatch,
            'colours' => array_map(fn (array $c) => ['name' => $c[0], 'hex' => $c[1]], $colours),
            'description' => $description,
            'materials' => $materials,
            'is_featured' => $featured,
        ];
    }
}
