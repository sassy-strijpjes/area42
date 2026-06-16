<x-mail::message>
# Your booking has been updated, {{ $guestName }}

Here are your updated booking details.

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

If you didn't request these changes or have any questions, please reply to this email.

Thanks,<br/>
{{ config('app.name') }}
</x-mail::message>
