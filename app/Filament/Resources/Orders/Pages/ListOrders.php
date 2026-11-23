<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'to_fulfil' => Tab::make('To pack & ship')
                ->badge(Order::toFulfil()->count() ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->toFulfil()->reorder('paid_at')),
            'review' => Tab::make('Needs review')
                ->badge(Order::needsReview()->count() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsReview()),
            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', OrderStatus::paidFor())),
            'all' => Tab::make('All, including abandoned'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'to_fulfil';
    }
}
