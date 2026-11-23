@php($address = $order->shipping_address ?? [])
@if ($address)
**Delivering to**<br>
{{ $order->customer_name }}<br>
@foreach (['line1', 'line2', 'city', 'state', 'postal_code', 'country'] as $field)
@if (! empty($address[$field])){{ $address[$field] }}<br>@endif
@endforeach
@endif
