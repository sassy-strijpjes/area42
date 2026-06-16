@use('Carbon\Carbon')

<div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
            <tr class="border-b border-zinc-200 dark:border-zinc-800">
                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-zinc-100">
                    Table
                </th>

                @foreach($this->weekDays() as $day)
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

                    @foreach($this->weekDays() as $day)
                        @php
                            $dayBookings = $this->bookings
                                ->where('table_id', $table->id)
                                ->filter(fn ($booking) =>
                                    Carbon::parse($booking->booking_start)->isSameDay($day)
                                );
                        @endphp

                        <td class="min-w-45 px-2 py-3 align-top">
                            <div class="space-y-2">
                                @foreach($dayBookings as $booking)
                                    <div
                                        @class([
                                            'rounded-lg border p-2',
                                            'border-green-200 bg-green-50 dark:border-green-950 dark:bg-green-950/40'
                                                => $booking->status === 'confirmed',
                                            'border-yellow-200 bg-yellow-50 dark:border-yellow-950 dark:bg-yellow-950/40'
                                                => $booking->status === 'waitlist',
                                            'border-red-200 bg-red-50 dark:border-red-950 dark:bg-red-950/40'
                                                => $booking->status === 'cancelled',
                                        ])
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="truncate text-sm font-medium">
                                                    {{ $booking->guest_name }}
                                                </div>

                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-300">
                                                    {{ Carbon::createFromFormat('H:i:s', $booking->booking_start)->format('H:i') }}

                                                    @if($booking->booking_end)
                                                        - {{ Carbon::parse($booking->booking_end)->format('H:i') }}
                                                    @endif
                                                </div>

                                                @if(! empty($booking->notes))
                                                    <button
                                                        wire:click="openNotes({{ $booking->id }})"
                                                        @class([
                                                            'mt-1 text-xs hover:underline underline-offset-2 transition',
                                                            'text-green-700 dark:text-green-400'
                                                                => $booking->status === 'confirmed',
                                                            'text-yellow-700 dark:text-yellow-300'
                                                                => $booking->status === 'waitlist',
                                                            'text-red-700 dark:text-red-400'
                                                                => $booking->status === 'cancelled',
                                                        ])
                                                    >
                                                        View notes
                                                    </button>
                                                @endif
                                            </div>

                                            @if(can('edit_restaurant-bookings') || can('cancel_restaurant-bookings'))
                                                <flux:dropdown position="bottom end">
                                                    <flux:button
                                                        icon="ellipsis-horizontal"
                                                        variant="ghost"
                                                        size="xs"
                                                    />

                                                    <flux:menu>
                                                        @can('edit_restaurant-bookings')
                                                            <flux:menu.item
                                                                :href="route('staff.restaurant.bookings.edit', $booking->id)"
                                                                icon="pencil-square"
                                                            >
                                                                Edit
                                                            </flux:menu.item>
                                                        @endcan

                                                        @can('cancel_restaurant-bookings')
                                                            @if($booking->status !== 'cancelled')
                                                                <flux:menu.item
                                                                    wire:click="cancel({{ $booking->id }})"
                                                                    icon="x-mark"
                                                                    variant="danger"
                                                                >
                                                                    Cancel
                                                                </flux:menu.item>
                                                            @endif
                                                        @endcan
                                                    </flux:menu>
                                                </flux:dropdown>
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
