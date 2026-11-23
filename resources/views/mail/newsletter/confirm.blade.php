<x-mail::message>
# One more step

Please confirm you'd like to receive the {{ config('app.name') }} newsletter: new arrivals, restocks and the occasional sale, no more than twice a month.

<x-mail::button :url="$confirmUrl">
Confirm my subscription
</x-mail::button>

If you didn't sign up, ignore this email and you won't hear from us again.

{{ config('app.name') }}
</x-mail::message>
