<?php

namespace App\Http\Resources;

use App\Enums\FulfilmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Media;
use App\Support\Vat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order as the customer sees it in their account: where it is (ordered →
 * packed → on its way), what's in it, and what they can do next.
 *
 * @mixin Order
 */
class AccountOrderResource extends JsonResource
{
    /** Free returns window, counted from dispatch. */
    private const RETURN_DAYS = 30;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'placedAt' => $this->created_at->toIso8601String(),
            'shippedAt' => $this->shipped_at?->toIso8601String(),
            'returnBy' => $this->shipped_at && ! $this->isFullyRefunded() ? $this->shipped_at->addDays(self::RETURN_DAYS)->toIso8601String() : null,
            'progress' => $this->progress(),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'shipping' => $this->shipping,
            'total' => $this->total,
            'vat' => $this->vat_rate ? ['rate' => Vat::label($this->vat_rate), 'amount' => $this->vat_amount] : null,
            'refunded' => $this->amount_refunded,
            'tracking' => $this->tracking_number || $this->tracking_url
                ? ['carrier' => $this->carrier, 'number' => $this->tracking_number, 'url' => $this->tracking_url]
                : null,
            'deliverTo' => $this->when($request->routeIs('account.orders.show'), fn () => [
                'name' => $this->customer_name,
                'lines' => collect($this->shipping_address ?? [])->only(['line1', 'line2', 'city', 'state', 'postal_code', 'country'])->filter()->values(),
            ]),
            'href' => route('account.orders.show', $this->reference),
            'items' => $this->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'colour' => $item->colour,
                'size' => $item->size,
                'quantity' => $item->quantity,
                'total' => $item->line_total,
                'image' => Media::url($item->product?->image),
                'swatch' => collect($item->product?->colours ?? [])->firstWhere('name', $item->colour)['hex'] ?? $item->product?->swatch ?? '#E3E7EE',
                'href' => $item->product?->is_active ? route('products.show', $item->product) : null,
            ]),
        ];
    }

    private function isFullyRefunded(): bool
    {
        return $this->status === OrderStatus::Refunded;
    }

    /**
     * @return array{step: int, steps: int, title: string, detail: string, tone: string}
     */
    private function progress(): array
    {
        $refundNote = $this->amount_refunded > 0 && ! $this->isFullyRefunded() ? ' Part of this order has been refunded.' : '';

        return match (true) {
            $this->isFullyRefunded() => [
                'step' => 0, 'steps' => 3, 'tone' => 'muted', 'title' => 'Refunded',
                'detail' => 'This order was refunded'.($this->refunded_at ? ' on '.$this->refunded_at->format('j F Y') : '').'.',
            ],
            $this->status === OrderStatus::Processing => [
                'step' => 0, 'steps' => 3, 'tone' => 'waiting', 'title' => 'Payment processing',
                'detail' => 'Your bank is confirming the payment. We’ll start on your order as soon as it clears.',
            ],
            $this->fulfilment_status === FulfilmentStatus::Shipped => [
                'step' => 3, 'steps' => 3, 'tone' => 'done', 'title' => 'On its way',
                'detail' => 'Sent '.$this->shipped_at?->format('l j F').($this->carrier ? " with {$this->carrier}" : '').'.'.$refundNote,
            ],
            $this->fulfilment_status === FulfilmentStatus::Packed => [
                'step' => 2, 'steps' => 3, 'tone' => 'active', 'title' => 'Packed',
                'detail' => 'Your order is packed and will leave us soon.'.$refundNote,
            ],
            default => [
                'step' => 1, 'steps' => 3, 'tone' => 'active', 'title' => 'Order confirmed',
                'detail' => 'We’re getting your order ready. We’ll email you when it’s sent.'.$refundNote,
            ],
        };
    }
}
