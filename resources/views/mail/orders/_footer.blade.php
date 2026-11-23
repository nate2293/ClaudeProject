@php($business = config('shop.business'))
<x-mail::subcopy>
{{ $business['legal_name'] }}@if ($business['address']) · {{ $business['address'] }}@endif
@if ($business['company_number']) · Company no. {{ $business['company_number'] }}@endif
@if (config('shop.vat.number')) · VAT no. {{ config('shop.vat.number') }}@endif
<br>Questions? Reply to this email or write to {{ $business['email'] }}.
</x-mail::subcopy>
