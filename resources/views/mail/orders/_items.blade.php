<x-mail::table>
| Item | Qty | Price |
| :--- | :-: | ---: |
@foreach ($order->items as $item)
| {{ $item->product_name }}<br><small>{{ $item->colour }}, size {{ $item->size }}</small> | {{ $item->quantity }} | {{ \App\Support\Money::format($item->line_total, $order->currency) }} |
@endforeach
| Delivery | | {{ $order->shipping === 0 ? 'Free' : \App\Support\Money::format($order->shipping, $order->currency) }} |
| **Total** | | **{{ \App\Support\Money::format($order->total, $order->currency) }}** |
@if ($order->vat_rate)
| <small>Includes VAT at {{ \App\Support\Vat::label($order->vat_rate) }}</small> | | <small>{{ \App\Support\Money::format($order->vat_amount, $order->currency) }}</small> |
@endif
</x-mail::table>
