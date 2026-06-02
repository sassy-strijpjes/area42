@use('Carbon\Carbon')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <flux:button.group>
                <flux:button
                    wire:click="$set('view', 'day')"
                    variant="{{ $view === 'day' ? 'primary' : 'filled' }}"
                    size="sm"
                >
                    Day
                </flux:button>

                <flux:button
                    wire:click="$set('view', 'week')"
                    variant="{{ $view === 'week' ? 'primary' : 'filled' }}"
                    size="sm"
                >
                    Week
                </flux:button>
            </flux:button.group>
        </div>

        <div class="flex items-center gap-2">
            <flux:badge>
                @if($view === 'day')
                    {{ Carbon::parse($currentDate)->format('D, d M Y') }}
                @else
                    Week of {{ Carbon::parse($currentDate)->startOfWeek()->format('d M') }}
                @endif
            </flux:badge>

            <flux:button
                wire:click="previous"
                variant="ghost"
                icon="chevron-left"
                size="sm"
            />

            <flux:button
                wire:click="next"
                variant="ghost"
                icon:trailing="chevron-right"
                size="sm"
            />

            <flux:dropdown>
                <flux:button
                    variant="ghost"
                    icon="adjustments-vertical"
                    size="sm"
                />

                <flux:menu>
                    <flux:menu.radio.group wire:model="sortBy">
                        <flux:menu.item wire:click="$toggle('showCancelled')">
                            {{ $showCancelled ? 'Hide cancelled bookings' : 'Show cancelled bookings' }}
                        </flux:menu.item>
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    @if($view === 'day')
        @include('components.tables.bookings.restaurant.partials.day-view')
    @else
        @include('components.tables.bookings.restaurant.partials.week-view')
    @endif

    <flux:modal wire:model="showNotesModal" class="max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">
                    Booking notes
                </flux:heading>

                @if($selectedBooking)
                    <div class="mt-2 text-sm text-zinc-500">
                        {{ $selectedBooking->guest_name }}, Table {{ $selectedBooking->table_id }}
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                @if($selectedBooking?->notes)
                    {{ $selectedBooking->notes }}
                @else
                    No notes added.
                @endif
            </div>

            <div class="flex justify-end">
                <flux:button
                    variant="ghost"
                    wire:click="$set('showNotesModal', false)"
                >
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal
        wire:model="bookingToCancel"
        class="max-w-md"
    >
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">
                    Cancel booking
                </flux:heading>

                <flux:text class="mt-2">
                    Are you sure you want to cancel this booking?
                    This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button
                    variant="ghost"
                    wire:click="$set('bookingToCancel', null)"
                >
                    Keep booking
                </flux:button>

                <flux:button
                    variant="danger"
                    wire:click="cancel"
                >
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
