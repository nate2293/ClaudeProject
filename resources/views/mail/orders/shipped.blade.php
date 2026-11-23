<x-mail::message>
# Your order is on its way

Good news: order **{{ $order->reference }}** left us on {{ $order->shipped_at?->timezone('Europe/London')->format('j F') }}@if ($order->carrier) with {{ $order->carrier }}@endif.

@if ($order->tracking_url)
<x-mail::button :url="$order->tracking_url">
Track your parcel
</x-mail::button>
@endif
@if ($order->tracking_number)
Tracking number: **{{ $order->tracking_number }}**
@endif

@include('mail.orders._items')

@include('mail.orders._address')

Thanks,<br>
{{ config('app.name') }}

@include('mail.orders._footer')
</x-mail::message>
