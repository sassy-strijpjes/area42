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
        </div>
    </div>

    @if($view === 'day')
        @include('components.tables.bookings.restaurant.partials.day-view')
    @else
        @include('components.tables.bookings.restaurant.partials.week-view')
    @endif
</div>
