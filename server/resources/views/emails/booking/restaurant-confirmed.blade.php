<x-mail::message>
# You're booked, {{ $guestName }}!

Your table reservation is confirmed. We look forward to seeing you.

<x-mail::table>
| | |
|:--|--:|
| **Date** | {{ $date }} |
| **Time** | {{ $timeStart }} – {{ $timeEnd }} |
| **Party size** | {{ $partySize }} {{ $partySize === 1 ? 'guest' : 'guests' }} |
@if ($notes)
| **Notes** | {{ $notes }} |
@endif
</x-mail::table>

Need to make changes? Simply reply to this email and we'll sort it out.

Thanks,<br/>
{{ config('app.name') }}
</x-mail::message>
