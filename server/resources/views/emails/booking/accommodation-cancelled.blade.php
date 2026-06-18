<x-mail::message>
# Your booking has been cancelled, {{ $guestName }}

We've cancelled your accommodation booking as requested.

<x-mail::table>
| | |
|:--|--:|
| **Type** | {{ $typeName }} |
| **Unit** | {{ $unitName }} |
| **Check-in** | {{ $checkIn }} |
| **Check-out** | {{ $checkOut }} |
</x-mail::table>

If this was a mistake or you'd like to rebook, please reply to this email and we'll be happy to help.

Thanks,<br/>
{{ config('app.name') }}
</x-mail::message>
