<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PaymentDisputes\PaymentDisputeResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\StripeEvents\StripeEventResource;
use App\Models\Order;
use App\Models\PaymentDispute;
use App\Models\StockLevel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The admin home page: what needs doing today. */
class NeedsAttention extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $toShip = Order::toFulfil()->count();
        $review = Order::needsReview()->count();
        $stuck = StripeEventResource::stuck()->count();
        $disputes = PaymentDispute::query()->whereNull('closed_at')->count();
        $soldOut = StockLevel::query()->whereColumn('on_hand', '<=', 'reserved')->count();

        return [
            Stat::make('To pack & ship', $toShip)
                ->description('Paid orders not yet shipped')
                ->color($toShip > 0 ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index', ['tab' => 'to_fulfil'])),
            Stat::make('Needs review', $review)
                ->description('Amount mismatches, disputes')
                ->color($review > 0 ? 'danger' : 'success')
                ->url(OrderResource::getUrl('index', ['tab' => 'review'])),
            Stat::make('Open disputes', $disputes)
                ->color($disputes > 0 ? 'danger' : 'success')
                ->url(PaymentDisputeResource::getUrl()),
            Stat::make('Stuck Stripe events', $stuck)
                ->description('Not processed after 10 minutes')
                ->color($stuck > 0 ? 'danger' : 'success')
                ->url(StripeEventResource::getUrl('index', ['filters' => ['processed' => ['value' => 0]]])),
            Stat::make('Sold-out variants', $soldOut)
                ->color($soldOut > 0 ? 'warning' : 'success')
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
