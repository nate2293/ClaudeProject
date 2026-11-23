<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\FulfilOrder;
use App\Actions\Orders\RefundOrder;
use App\Enums\FulfilmentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\MoneyInput;
use App\Inventory\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\PaymentException;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

/**
 * @property Order $record
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    /** Carriers offered when shipping; anything else can be typed. */
    private const CARRIERS = ['Royal Mail', 'Evri', 'DPD', 'DHL', 'Parcelforce', 'Yodel'];

    protected function getHeaderActions(): array
    {
        return [
            $this->packAction(),
            $this->shipAction(),
            ActionGroup::make([
                $this->refundAction(),
                $this->restockAction(),
                $this->resolveReviewAction(),
            ])->label('More')->button()->color('gray'),
        ];
    }

    private function packAction(): Action
    {
        return Action::make('pack')
            ->label('Mark packed')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->visible(fn () => $this->isShippable() && $this->record->fulfilment_status === FulfilmentStatus::Unfulfilled)
            ->action(function (FulfilOrder $fulfil) {
                $fulfil->pack($this->record)
                    ? Notification::make()->title('Marked as packed')->success()->send()
                    : Notification::make()->title('This order changed. Refresh and try again.')->warning()->send();
            });
    }

    private function shipAction(): Action
    {
        return Action::make('ship')
            ->label('Mark shipped')
            ->icon(Heroicon::OutlinedTruck)
            ->visible(fn () => $this->isShippable() && $this->record->fulfilment_status !== FulfilmentStatus::Shipped)
            ->modalDescription('The customer is emailed that their order is on its way, with tracking if you add it.')
            ->schema([
                Select::make('carrier')
                    ->options(array_combine(self::CARRIERS, self::CARRIERS))
                    ->searchable()
                    ->createOptionForm([TextInput::make('carrier')->required()->maxLength(100)])
                    ->createOptionUsing(fn (array $data) => $data['carrier']),
                TextInput::make('tracking_number')->maxLength(100),
                TextInput::make('tracking_url')->label('Tracking link')->url()->rules(['url:http,https'])->maxLength(500)
                    ->helperText('Shown to the customer as a "Track your parcel" button.'),
            ])
            ->modalSubmitActionLabel('Mark shipped and email customer')
            ->action(function (array $data, FulfilOrder $fulfil) {
                $fulfil->ship($this->record, $data['carrier'] ?? null, $data['tracking_number'] ?? null, $data['tracking_url'] ?? null)
                    ? Notification::make()->title('Marked as shipped')->body('The customer has been emailed.')->success()->send()
                    : Notification::make()->title('This order changed. Refresh and try again.')->warning()->send();
            });
    }

    private function refundAction(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->icon(Heroicon::OutlinedReceiptRefund)
            ->color('danger')
            ->visible(fn () => $this->record->wasPaidFor() && $this->record->refundableAmount() > 0 && $this->record->payment_reference)
            ->modalDescription(fn () => 'Up to '.Money::format($this->record->refundableAmount(), $this->record->currency)
                .' can be refunded. The money goes back to the customer\'s card through Stripe, and they are emailed.')
            ->fillForm(fn () => ['amount' => $this->record->refundableAmount()])
            ->schema(fn () => [
                MoneyInput::make('amount')->label('Amount to refund')->required()
                    ->rules(['numeric', 'gt:0', 'lte:'.($this->record->refundableAmount() / 100)]),
                $this->restockFields('Put returned items back into stock (optional)'),
            ])
            ->requiresConfirmation()
            ->modalSubmitActionLabel('Refund')
            ->action(function (array $data, RefundOrder $refund) {
                try {
                    $refund->handle($this->record, (int) $data['amount'], $this->restockQuantities($data));
                } catch (PaymentException|InvalidArgumentException $e) {
                    Notification::make()->title('Refund failed')->body($e->getMessage())->danger()->persistent()->send();

                    return;
                }

                $this->record->refresh();
                Notification::make()->title('Refunded '.Money::format((int) $data['amount'], $this->record->currency))->success()->send();
            });
    }

    private function restockAction(): Action
    {
        return Action::make('restock')
            ->label('Restock returned items')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->visible(fn () => $this->record->wasPaidFor() && $this->record->items->contains(fn (OrderItem $item) => $item->restockable() > 0))
            ->modalDescription('Puts units back on sale without refunding. Use Refund instead if money is also going back.')
            ->schema(fn () => [$this->restockFields('Units to put back into stock')])
            ->action(function (array $data, Inventory $inventory) {
                $restocked = 0;

                foreach ($this->record->items as $item) {
                    $restocked += $inventory->restock($item, $this->restockQuantities($data)[$item->id] ?? 0);
                }

                Notification::make()->title("Restocked {$restocked} ".str('unit')->plural($restocked))->success()->send();
            });
    }

    private function resolveReviewAction(): Action
    {
        return Action::make('resolveReview')
            ->label('Clear review flag')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->visible(fn () => filled($this->record->review_reason))
            ->requiresConfirmation()
            ->modalDescription(fn () => "Clear: \"{$this->record->review_reason}\"")
            ->action(function () {
                $this->record->forceFill(['review_reason' => null])->save();
                Notification::make()->title('Review flag cleared')->success()->send();
            });
    }

    /** One number field per line that still has units to restock. */
    private function restockFields(string $label): Fieldset
    {
        return Fieldset::make($label)->columns(1)->schema(
            $this->record->items
                ->filter(fn (OrderItem $item) => $item->restockable() > 0)
                ->map(fn (OrderItem $item) => TextInput::make("restock.{$item->id}")
                    ->label("{$item->product_name} ({$item->colour}, {$item->size})")
                    ->numeric()->integer()->minValue(0)->maxValue($item->restockable())->default(0)
                    ->suffix("of {$item->restockable()}"))
                ->values()
                ->all(),
        );
    }

    /** @return array<int, int> */
    private function restockQuantities(array $data): array
    {
        return array_map('intval', array_filter($data['restock'] ?? []));
    }

    private function isShippable(): bool
    {
        return in_array($this->record->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true);
    }
}
