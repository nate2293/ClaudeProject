<?php

namespace App\Http\Middleware;

use App\Cart\Cart;
use App\Models\Category;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                // Google-only customers have no password: settings offer "Set a password" instead.
                'hasPassword' => $request->user()?->hasPassword() ?? false,
            ],
            'navigation' => fn () => array_map(fn (array $category) => [
                'label' => $category['name'],
                'href' => route('shop.category', $category['slug']),
            ], Category::navigation()),
            'shop' => [
                'currency' => config('shop.currency'),
                'freeShippingThreshold' => config('shop.shipping.free_threshold'),
                'standardShippingRate' => config('shop.shipping.standard_rate'),
                'maxQuantity' => config('shop.max_quantity_per_line'),
                'lowStockThreshold' => config('shop.stock.low_stock_threshold'),
                'business' => [
                    'legalName' => config('shop.business.legal_name'),
                    'address' => config('shop.business.address'),
                    'email' => config('shop.business.email'),
                    'companyNumber' => config('shop.business.company_number'),
                    'vatNumber' => config('shop.vat.number'),
                    'icoNumber' => config('shop.business.ico_number'),
                    'host' => config('shop.business.host'),
                    'emailProvider' => config('shop.business.email_provider'),
                ],
            ],
            'cart' => fn () => ['count' => app(Cart::class)->count()],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
