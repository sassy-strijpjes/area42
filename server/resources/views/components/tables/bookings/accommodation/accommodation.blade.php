@use('Carbon\Carbon')

<flux:table :paginate="$this->bookings">
    <flux:table.columns>
        <flux:table.column
            sortable
            :sorted="$sortBy === 'guest_name'"
            :direction="$sortDirection"
            wire:click="sort('guest_name')"
            width="20%"
        >
            Guest
        </flux:table.column>

        <flux:table.column width="15%">Type / Unit</flux:table.column>

        <flux:table.column
            sortable
            :sorted="$sortBy === 'check_in'"
            :direction="$sortDirection"
            wire:click="sort('check_in')"
        >
            Check-in
        </flux:table.column>

        <flux:table.column
            sortable
            :sorted="$sortBy === 'check_out'"
            :direction="$sortDirection"
            wire:click="sort('check_out')"
        >
            Check-out
        </flux:table.column>

        <flux:table.column>Guests</flux:table.column>

        <flux:table.column
            sortable
            :sorted="$sortBy === 'status'"
            :direction="$sortDirection"
            wire:click="sort('status')"
        >
            Status
        </flux:table.column>

        <flux:table.column>Payment</flux:table.column>

        <flux:table.column
            sortable
            :sorted="$sortBy === 'total_price'"
            :direction="$sortDirection"
            wire:click="sort('total_price')"
        >
            Total
        </flux:table.column>

        <flux:table.column></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @forelse($this->bookings as $booking)
            @php
                $statusColor = match($booking->status) {
                    'confirmed'   => 'green',
                    'checked_in'  => 'blue',
                    'checked_out' => 'zinc',
                    'cancelled'   => 'red',
                    default       => 'zinc',
                };
                $paymentColor = match($booking->payment_status) {
                    'paid'    => 'green',
                    'partial' => 'yellow',
                    default   => 'zinc',
                };
            @endphp

            <flux:table.row :key="$booking->id">

                <flux:table.cell class="font-medium">
                    <div>{{ $booking->guest_name }}</div>
                    @if($booking->guest_email)
                        <div class="text-xs text-zinc-400">{{ $booking->guest_email }}</div>
                    @endif
                </flux:table.cell>

                <flux:table.cell>
                    <div class="space-y-1">
                        {{ $booking->type_name }}
                        <div class="text-xs text-zinc-500">{{ $booking->unit_name }}</div>
                    </div>
                </flux:table.cell>

                <flux:table.cell>
                    {{ Carbon::parse($booking->check_in)->format('d M Y') }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ Carbon::parse($booking->check_out)->format('d M Y') }}
                </flux:table.cell>

                <flux:table.cell>{{ $booking->guests }}</flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" :color="$statusColor">
                        {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                    </flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" :color="$paymentColor">
                        {{ ucfirst($booking->payment_status) }}
                    </flux:badge>
                </flux:table.cell>

                <flux:table.cell class="font-medium">
                    €{{ number_format($booking->total_price, 2) }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                        <flux:menu>
                            <flux:menu.item
                                icon="pencil-square"
                                :href="route('staff.accommodation.bookings.edit', $booking->id)"
                            >
                                Edit
                            </flux:menu.item>

                            @if(!in_array($booking->status, ['cancelled', 'checked_out']))
                                <flux:menu.separator />
                                <flux:menu.item
                                    variant="danger"
                                    icon="x-circle"
                                    x-on:click="$flux.modal('cancel-booking-{{ $booking->id }}').show()"
                                >
                                    Cancel
                                </flux:menu.item>
                            @endif
                        </flux:menu>
                    </flux:dropdown>

                    <flux:modal name="cancel-booking-{{ $booking->id }}" class="max-w-md">
                        <div class="space-y-4">
                            <flux:heading size="lg">Cancel booking</flux:heading>
                            <flux:text>
                                Cancel the booking for <strong>{{ $booking->guest_name }}</strong>
                                ({{ $booking->unit_name }},
                                {{ Carbon::parse($booking->check_in)->format('d M') }}–{{ Carbon::parse($booking->check_out)->format('d M Y') }})?
                                This cannot be undone.
                            </flux:text>
                            <div class="flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button variant="ghost">Keep booking</flux:button>
                                </flux:modal.close>
                                <flux:button
                                    variant="danger"
                                    wire:click="cancel({{ $booking->id }})"
                                >
                                    Cancel booking
                                </flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </flux:table.cell>

            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="9" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:heading size="sm">No bookings found</flux:heading>
                        <flux:text class="max-w-sm">Try adjusting your search or create a new booking.</flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
