<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Cloth names shown on the home page, each with the text that identifies it
     * in a product's materials. Only cloths actually in the collection are shown.
     */
    private const CLOTHS = [
        'Merino wool' => 'merino',
        'Lambswool' => 'lambswool',
        'Cashmere' => 'cashmere',
        'Linen' => 'linen',
        'Organic cotton' => 'organic cotton',
        'Waxed cotton' => 'waxed cotton',
        'Selvedge denim' => 'selvedge denim',
        'Flannel' => 'flannel',
        'TENCEL™ lyocell' => 'lyocell',
        'Canvas' => 'canvas',
        'Leather' => 'leather',
        'Recycled nylon' => 'recycled nylon',
    ];

    public function __invoke(): Response
    {
        return Inertia::render('home', [
            'featuredProducts' => ProductResource::collection(
                Product::active()->where('is_featured', true)->with('category', 'stockLevels')->withStockStatus()->latest('id')->take(8)->get(),
            ),
            'collection' => $this->collection(Product::active()->latest('id')->get(['id', 'name', 'materials', 'colours', 'swatch'])),
        ]);
    }

    /**
     * Figures and names about the whole collection, all taken from the catalogue.
     *
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function collection(Collection $products): array
    {
        $materials = $products->pluck('materials')->filter()->map(fn (string $m) => mb_strtolower($m));

        return [
            'pieces' => $products->count(),
            'cloths' => collect(self::CLOTHS)
                ->filter(fn (string $needle) => $materials->contains(fn (string $m) => str_contains($m, $needle)))
                ->keys()
                ->values(),
            'names' => $products->take(16)->map(fn (Product $p) => [
                'name' => $p->name,
                'swatch' => $p->colours[0]['hex'] ?? $p->swatch,
            ])->values(),
            'freeDeliveryOver' => (int) config('shop.shipping.free_threshold'),
            'returnDays' => (int) config('shop.return_days'),
        ];
    }
}
