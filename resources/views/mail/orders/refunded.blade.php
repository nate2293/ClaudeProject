<x-mail::message>
# Your refund is on its way

We've refunded **{{ \App\Support\Money::format($amount, $order->currency) }}** for order **{{ $order->reference }}**
to the card you paid with. It usually appears within 5–10 working days, depending on your bank.

@if ($order->amount_refunded < ($order->amount_paid ?? $order->total))
In total, {{ \App\Support\Money::format($order->amount_refunded, $order->currency) }} of your {{ \App\Support\Money::format($order->amount_paid ?? $order->total, $order->currency) }} payment has now been refunded.
@else
Your payment of {{ \App\Support\Money::format($order->amount_paid ?? $order->total, $order->currency) }} has now been refunded in full.
@endif

Thanks,<br>
{{ config('app.name') }}

@include('mail.orders._footer')
</x-mail::message>
