<x-mail::message>
# Booking confirmed, {{ $guestName }}!

Your accommodation has been reserved. We look forward to welcoming you.

<x-mail::table>
| | |
|:--|--:|
| **Type** | {{ $typeName }} |
| **Unit** | {{ $unitName }} |
| **Check-in** | {{ $checkIn }} |
| **Check-out** | {{ $checkOut }} |
| **Nights** | {{ $nights }} |
| **Guests** | {{ $guests }} {{ $guests === 1 ? 'guest' : 'guests' }} |
| **Total** | €{{ $totalPrice }} |
| **Payment** | {{ $paymentStatus }} |
@if ($specialRequests)
| **Special requests** | {{ $specialRequests }} |
@endif
</x-mail::table>

Need to make changes? Simply reply to this email and we'll sort it out.

Thanks,<br/>
{{ config('app.name') }}
</x-mail::message>
