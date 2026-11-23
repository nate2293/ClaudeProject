<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Queries\CatalogueFilters;
use App\Queries\CatalogueQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    /** All products: /shop */
    public function index(Request $request): Response
    {
        return $this->render($request);
    }

    /** One department: /shop/{category} */
    public function category(Request $request, Category $category): Response
    {
        return $this->render($request, $category);
    }

    private function render(Request $request, ?Category $category = null): Response
    {
        $filters = CatalogueFilters::fromRequest($request);
        $query = new CatalogueQuery($category, $filters);

        return Inertia::render('shop/index', [
            'category' => $category ? new CategoryResource($category) : null,
            'products' => ProductResource::collection($query->paginate(config('shop.products_per_page'))),
            'types' => $query->availableTypes(),
            'filters' => $filters->toArray(),
        ]);
    }
}
