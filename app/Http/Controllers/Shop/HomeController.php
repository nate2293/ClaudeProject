<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('home', [
            'categories' => CategoryResource::collection(
                Category::ordered()->withCount(['products' => fn ($query) => $query->active()])->get(),
            ),
            'featuredProducts' => ProductResource::collection(
                Product::active()->where('is_featured', true)->with('category')->withStockStatus()->latest('id')->take(8)->get(),
            ),
        ]);
    }
}
