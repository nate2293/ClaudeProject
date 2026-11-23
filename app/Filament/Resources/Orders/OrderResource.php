<?php

namespace App\Filament\Resources\Orders;

use App\Enums\FulfilmentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Support\StripeDashboard;
use App\Models\Order;
use App\Support\Money;
use App\Support\Vat;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Orders are created by checkout and changed only through actions (pack, ship,
 * refund, restock), never edited directly: totals and payment state must match
 * what the payment provider holds.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Order::toFulfil()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function statusColour(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Paid => 'success',
            OrderStatus::Processing, OrderStatus::Pending => 'warning',
            OrderStatus::PartiallyRefunded, OrderStatus::Refunded => 'info',
            OrderStatus::Failed, OrderStatus::Cancelled => 'gray',
        };
    }

    public static function fulfilmentColour(FulfilmentStatus $status): string
    {
        return match ($status) {
            FulfilmentStatus::Unfulfilled => 'warning',
            FulfilmentStatus::Packed => 'info',
            FulfilmentStatus::Shipped => 'success',
        };
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn (string $field) => fn (Order $record) => $record->{$field} === null ? null : Money::format($record->{$field}, $record->currency);

        return $schema->components([
            Section::make('Needs review')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->iconColor('danger')
                ->visible(fn (Order $record) => filled($record->review_reason))
                ->columnSpanFull()
                ->schema([TextEntry::make('review_reason')->hiddenLabel()]),

            Grid::make(3)->columnSpanFull()->schema([
                Section::make('Order')->schema([
                    TextEntry::make('reference')->copyable()->weight('bold'),
                    TextEntry::make('status')->badge()
                        ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                        ->color(fn (OrderStatus $state) => self::statusColour($state)),
                    TextEntry::make('fulfilment_status')->label('Fulfilment')->badge()
                        ->formatStateUsing(fn (FulfilmentStatus $state) => $state->label())
                        ->color(fn (FulfilmentStatus $state) => self::fulfilmentColour($state)),
                    TextEntry::make('created_at')->label('Placed')->dateTime('j M Y, H:i'),
                    TextEntry::make('paid_at')->dateTime('j M Y, H:i')->placeholder('Not paid'),
                ]),
                Section::make('Customer')->schema([
                    TextEntry::make('customer_name')->placeholder('—'),
                    TextEntry::make('email')->copyable()->placeholder('—'),
                    TextEntry::make('shipping_address')->label('Delivery address')
                        ->state(fn (Order $record) => collect($record->shipping_address ?? [])
                            ->only(['line1', 'line2', 'city', 'state', 'postal_code', 'country'])->filter()->implode("\n"))
                        ->placeholder('—')
                        ->extraAttributes(['class' => 'whitespace-pre-line']),
                ]),
                Section::make('Payment')->schema([
                    TextEntry::make('total')->state($money('total')),
                    TextEntry::make('vat_amount')->label('Includes VAT')
                        ->state(fn (Order $record) => $record->vat_rate ? Money::format($record->vat_amount, $record->currency).' ('.Vat::label($record->vat_rate).')' : 'None'),
                    TextEntry::make('amount_paid')->state($money('amount_paid'))->placeholder('—'),
                    TextEntry::make('amount_refunded')->state($money('amount_refunded'))
                        ->visible(fn (Order $record) => $record->amount_refunded > 0),
                    TextEntry::make('payment_reference')->label('Stripe payment')->placeholder('—')
                        ->url(fn (Order $record) => $record->payment_reference ? StripeDashboard::payment($record->payment_reference) : null, true),
                ]),
            ]),

            Section::make('Items')->columnSpanFull()->schema([
                RepeatableEntry::make('items')->hiddenLabel()->columns(6)->schema([
                    TextEntry::make('product_name')->label('Product')->columnSpan(2),
                    TextEntry::make('variant')->label('Colour / size')->state(fn ($record) => "{$record->colour} / {$record->size}"),
                    TextEntry::make('quantity')->label('Qty'),
                    TextEntry::make('line_total')->label('Total')->state(fn ($record) => Money::format($record->line_total, $record->order->currency)),
                    TextEntry::make('restocked_quantity')->label('Restocked')
                        ->state(fn ($record) => $record->restocked_quantity ?: '—'),
                ]),
                TextEntry::make('totals')->hiddenLabel()->alignEnd()
                    ->state(fn (Order $record) => 'Subtotal '.Money::format($record->subtotal, $record->currency)
                        .' · Delivery '.($record->shipping === 0 ? 'free' : Money::format($record->shipping, $record->currency))
                        .' · Total '.Money::format($record->total, $record->currency)),
            ]),

            Section::make('Shipping')
                ->columnSpanFull()->columns(4)
                ->visible(fn (Order $record) => $record->fulfilment_status !== FulfilmentStatus::Unfulfilled)
                ->schema([
                    TextEntry::make('packed_at')->dateTime('j M Y, H:i')->placeholder('—'),
                    TextEntry::make('shipped_at')->dateTime('j M Y, H:i')->placeholder('—'),
                    TextEntry::make('carrier')->placeholder('—'),
                    TextEntry::make('tracking_number')->copyable()->placeholder('—')
                        ->url(fn (Order $record) => $record->tracking_url, true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->searchable()->weight('bold'),
                TextColumn::make('created_at')->label('Placed')->dateTime('j M, H:i')->sortable(),
                TextColumn::make('customer_name')->label('Customer')->searchable()
                    ->description(fn (Order $record) => $record->email),
                TextColumn::make('total')->sortable()
                    ->formatStateUsing(fn (int $state, Order $record) => Money::format($state, $record->currency)),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => self::statusColour($state)),
                TextColumn::make('fulfilment_status')->label('Fulfilment')->badge()
                    ->formatStateUsing(fn (FulfilmentStatus $state) => $state->label())
                    ->color(fn (FulfilmentStatus $state) => self::fulfilmentColour($state)),
                IconColumn::make('review_reason')->label('Review')
                    ->icon(fn (?string $state) => $state ? Heroicon::OutlinedExclamationTriangle : null)
                    ->color('danger')
                    ->tooltip(fn (Order $record) => $record->review_reason),
            ])
            ->filters([
                SelectFilter::make('status')->multiple()
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('fulfilment_status')->label('Fulfilment')
                    ->options(collect(FulfilmentStatus::cases())->mapWithKeys(fn (FulfilmentStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
