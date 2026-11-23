<x-mail::message>
# Thank you for your order

Hi {{ \Illuminate\Support\Str::before($order->customer_name ?? 'there', ' ') }}, we've received your payment and we're getting your order ready.
We'll email you again as soon as it's on its way.

**Order reference:** {{ $order->reference }}<br>
**Placed:** {{ $order->created_at->timezone('Europe/London')->format('j F Y') }}

@include('mail.orders._items')

@include('mail.orders._address')

Delivery usually takes 2–4 working days. You can return anything within 30 days. See our [returns policy]({{ route('pages.returns') }}).

Thanks,<br>
{{ config('app.name') }}

@include('mail.orders._footer')
</x-mail::message>
