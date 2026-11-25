<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('home', [
            'featuredProducts' => ProductResource::collection(
                Product::active()->where('is_featured', true)->with('category', 'stockLevels')->withStockStatus()->latest('id')->take(8)->get(),
            ),
        ]);
    }
}
