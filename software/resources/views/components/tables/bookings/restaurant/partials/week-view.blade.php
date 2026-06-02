@use('Carbon\Carbon')
<div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
            <tr class="border-b border-zinc-200 dark:border-zinc-800">
                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-zinc-100">
                    Table
                </th>

                @foreach($this->weekDays as $day)
                    <th class="px-4 py-3 text-center">
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $day->format('D') }}
                        </div>

                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $day->format('d M') }}
                        </div>
                    </th>
                @endforeach
            </tr>
            </thead>

            <tbody>
                @foreach($this->tables as $table)
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <td class="px-4 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $table->name }}
                        </td>

                        @foreach($this->weekDays as $day)
                            @php
                                $dayBookings = $this->bookings
                                    ->where('table_id', $table->id)
                                    ->filter(fn ($booking) =>
                                        Carbon::parse($booking->booking_start)
                                            ->isSameDay($day)
                                    );
                            @endphp

                            <td class="min-w-45 px-2 py-3 align-top">
                                <div class="space-y-2">
                                    @foreach($dayBookings as $booking)
                                        <div
                                            class="rounded-lg border border-green-200 bg-green-50 p-2 dark:border-green-950 dark:bg-green-950/40"
                                        >
                                            <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $booking->guest_name }}
                                            </div>

                                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-300">
                                                {{ Carbon::parse($booking->booking_start)->format('H:i') }}

                                                @if($booking->booking_end)
                                                    -
                                                    {{ Carbon::parse($booking->booking_end)->format('H:i') }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
