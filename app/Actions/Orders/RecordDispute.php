<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\PaymentDispute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Records a card dispute (chargeback) reported by the payment provider. A new
 * dispute is logged as critical and flags its order for review: there is a
 * deadline to respond with evidence in the provider's dashboard.
 */
class RecordDispute
{
    /**
     * @param  array{id: string, amount: int, currency: string, reason?: ?string, status: string, payment_reference?: ?string, evidence_due_by?: ?int}  $dispute
     */
    public function handle(array $dispute): PaymentDispute
    {
        $order = isset($dispute['payment_reference'])
            ? Order::firstWhere('payment_reference', $dispute['payment_reference'])
            : null;

        $record = PaymentDispute::firstOrNew(['id' => $dispute['id']]);
        $isNew = ! $record->exists;

        $record->fill([
            'order_id' => $order?->id,
            'payment_reference' => $dispute['payment_reference'] ?? null,
            'amount' => $dispute['amount'],
            'currency' => $dispute['currency'],
            'reason' => $dispute['reason'] ?? null,
            'status' => $dispute['status'],
            'evidence_due_by' => isset($dispute['evidence_due_by']) ? Carbon::createFromTimestamp($dispute['evidence_due_by']) : null,
        ]);

        if (! $record->isOpen()) {
            $record->closed_at ??= now();
        }

        $record->save();

        if ($isNew) {
            Log::critical('Payment disputed: respond with evidence in the Stripe dashboard before the deadline.', [
                'dispute' => $record->id,
                'order' => $order?->reference,
                'reason' => $record->reason,
                'evidence_due_by' => $record->evidence_due_by?->toDateString(),
            ]);

            if ($order && ! $order->review_reason) {
                $order->forceFill(['review_reason' => "Card payment disputed ({$record->reason}). Respond in Stripe by {$record->evidence_due_by?->toFormattedDateString()}."])->save();
            }
        }

        return $record;
    }
}
