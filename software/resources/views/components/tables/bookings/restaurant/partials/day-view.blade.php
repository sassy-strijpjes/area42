@use('Carbon\Carbon')
<div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
        <div class="flex items-center justify-between">
            <h3 class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ Carbon::parse($currentDate)->format('l, d M Y') }}
            </h3>

            <div>
                <flux:badge>
                    {{ $this->bookings->where('status', '!=', 'cancelled')->count() }}
                    active
                </flux:badge>

                @if($showCancelled)
                    <flux:badge color="red">
                        {{ $this->bookings->where('status', 'cancelled')->count() }}
                        cancelled
                    </flux:badge>
                @endif
            </div>
        </div>
    </div>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
        @foreach($this->tables as $table)
            <div class="grid grid-cols-12">
                <div class="col-span-2 border-r border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $table->name }}
                    </div>
                </div>

                <div class="col-span-10 p-4">
                    <div class="flex flex-wrap gap-2">
                        @forelse(($this->bookings->groupBy('table_id')[$table->id] ?? collect()) as $booking)
                            <div
                                @class([
                                    'min-w-45 rounded-lg border p-3',
                                    'border-green-200 bg-green-50 dark:border-green-950 dark:bg-green-950/40'
                                        => $booking->status !== 'cancelled',
                                    'border-red-200 bg-red-50 dark:border-red-950 dark:bg-red-950/40'
                                        => $booking->status === 'cancelled',
                                ])
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <div
                                            @class([
                                                'truncate text-sm font-medium',
                                                'text-zinc-900 dark:text-zinc-100'
                                                    => $booking->status !== 'cancelled',
                                                'line-through text-red-700 dark:text-red-300'
                                                    => $booking->status === 'cancelled',
                                            ])
                                        >
                                            {{ $booking->guest_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-300">
                                            {{ Carbon::createFromFormat('H:i:s', $booking->booking_start)->format('H:i') }}

                                            @if($booking->booking_end)
                                                -
                                                {{ Carbon::parse($booking->booking_end)->format('H:i') }}
                                            @endif
                                        </div>

                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-300">
                                            {{ $booking->party_size }} guests
                                        </div>
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
                        @empty
                            <span class="text-sm text-zinc-500 dark:text-zinc-300">
                                No bookings
                            </span>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
