<div>
    @if ($confirmed)
        <div class="min-h-screen bg-white dark:bg-zinc-900 flex items-center justify-center px-6">
            <div class="max-w-md w-full text-center space-y-5">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/15 text-green-600 dark:text-green-400 mx-auto">
                    <flux:icon.check-circle variant="outline" class="size-8" />
                </div>

                <div class="space-y-2">
                    <flux:heading size="xl">You're all set!</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        Your table has been reserved. A confirmation has been sent to <strong>{{ $confirmedEmail }}</strong>.
                    </flux:text>
                </div>

                <flux:card class="text-left space-y-3">
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Date</flux:text>
                        <flux:text class="font-medium">{{ \Carbon\Carbon::parse($confirmedDate)->format('D, d M Y') }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Time</flux:text>
                        <flux:text class="font-medium">{{ $confirmedTime }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Party size</flux:text>
                        <flux:text class="font-medium">{{ $confirmedPartySize }} {{ $confirmedPartySize === 1 ? 'guest' : 'guests' }}</flux:text>
                    </div>
                    @if ($confirmedNotes)
                        <flux:separator />
                        <div class="flex justify-between text-sm">
                            <flux:text class="text-zinc-600 dark:text-zinc-500">Notes</flux:text>
                            <flux:text class="font-medium">{{ $confirmedNotes }}</flux:text>
                        </div>
                    @endif
                </flux:card>

                <flux:button href="{{ route('home') }}" variant="ghost" class="w-full">
                    Back home
                </flux:button>
            </div>
        </div>

    @else
        <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
            <div class="mx-auto max-w-3xl px-6 py-16 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400 mx-auto mb-5">
                    <flux:icon.utensils-crossed variant="outline" class="size-6" />
                </div>

                <flux:heading size="xl" level="1" class="text-3xl! sm:text-4xl! font-bold! leading-tight!">
                    Reserve a table
                </flux:heading>

                <flux:text size="lg" class="mt-3 text-zinc-500 dark:text-zinc-400">
                    Pick your date, time, and party size and we will handle the rest.
                </flux:text>
            </div>
        </div>

        <div class="mx-auto max-w-xl px-6 py-14">
            <form wire:submit.prevent="book" class="space-y-8">
                <div class="space-y-4">
                    <flux:heading size="sm" class="text-zinc-400 uppercase tracking-widest text-xs!">
                        When are you coming?
                    </flux:heading>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input
                            wire:model.live="booking_date"
                            type="date"
                            label="Date"
                            :min="today()"
                        />

                        <flux:input
                            wire:model.live.debounce.500ms="party_size"
                            type="number"
                            label="Guests"
                            min="1"
                            :max="$maxPartySize"
                            placeholder="2"
                        />
                    </div>

                    <div class="space-y-2">
                        <flux:label>Time slot</flux:label>

                        @if ($booking_date)
                            @if (count($availableSlots) > 0)
                                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                    @foreach ($availableSlots as $slot)
                                        <button
                                            type="button"
                                            wire:click="selectSlot('{{ $slot }}')"
                                            @class([
                                                'rounded-lg border px-3 py-2 text-sm font-medium transition-colors duration-150',
                                                'border-zinc-500 bg-zinc-50 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-400 dark:border-zinc-500' => $booking_time === $slot,
                                                'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 text-zinc-700 dark:text-zinc-300' => $booking_time !== $slot,
                                            ])
                                        >
                                            {{ $slot }}
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3">
                                    <flux:text class="text-zinc-500 text-sm">No available slots for this date. Please try another day.</flux:text>
                                </div>
                            @endif
                        @else
                            <div class="rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700 px-4 py-3">
                                <flux:text class="text-zinc-400 text-sm">Select a date to see available time slots.</flux:text>
                            </div>
                        @endif

                        @error('booking_time')
                        <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                        @enderror
                    </div>
                </div>

                <flux:separator />

                <div class="space-y-4">
                    <flux:heading size="sm" class="text-zinc-400 uppercase tracking-widest text-xs!">
                        Your details
                    </flux:heading>

                    <flux:input
                        wire:model="guest_name"
                        label="Full name"
                        placeholder="Jane Smith"
                    />

                    <flux:input
                        wire:model="guest_email"
                        type="email"
                        label="Email address"
                        placeholder="jane@example.com"
                        description="We'll send your confirmation here."
                    />

                    <flux:textarea
                        wire:model="notes"
                        label="Notes"
                        placeholder="Allergies, birthday, high chair, special requests…"
                        rows="3"
                    />
                </div>

                <flux:separator />

                @if ($booking_date && $booking_time && $party_size)
                    <flux:card class="bg-zinc-50 dark:bg-zinc-500/10 border-zinc-200 dark:border-zinc-500/30">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <flux:text class="text-zinc-400">Date</flux:text>
                                <flux:text class="font-medium">{{ \Carbon\Carbon::parse($booking_date)->format('D, d M Y') }}</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text class="text-zinc-400">Time</flux:text>
                                <flux:text class="font-medium">{{ $booking_time }}</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text class="text-zinc-400">Party size</flux:text>
                                <flux:text class="font-medium">{{ $party_size }} {{ $party_size == 1 ? 'guest' : 'guests' }}</flux:text>
                            </div>
                        </div>
                    </flux:card>
                @endif

                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Confirm reservation</span>
                    <span wire:loading>Reserving…</span>
                </flux:button>
            </form>
        </div>
    @endif
</div>
