@use('Carbon\Carbon')

<div>
    @if ($step === 'confirmed')
        <div class="min-h-screen bg-white dark:bg-zinc-900 flex items-center justify-center px-6">
            <div class="max-w-md w-full text-center space-y-5">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/15 text-green-600 dark:text-green-400 mx-auto">
                    <flux:icon.check-circle variant="outline" class="size-8" />
                </div>

                <div class="space-y-2">
                    <flux:heading size="xl">You're booked!</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        Your accommodation has been reserved. A confirmation has been sent to <strong>{{ $confirmedEmail }}</strong>.
                    </flux:text>
                </div>

                <flux:card class="text-left space-y-3">
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Type</flux:text>
                        <flux:text class="font-medium">{{ $confirmedTypeName }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Unit</flux:text>
                        <flux:text class="font-medium">{{ $confirmedUnitName }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Check-in</flux:text>
                        <flux:text class="font-medium">{{ Carbon::parse($confirmedCheckIn)->format('D, d M Y') }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Check-out</flux:text>
                        <flux:text class="font-medium">{{ Carbon::parse($confirmedCheckOut)->format('D, d M Y') }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Guests</flux:text>
                        <flux:text class="font-medium">{{ $confirmedGuests }} {{ $confirmedGuests === 1 ? 'guest' : 'guests' }}</flux:text>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-zinc-600 dark:text-zinc-500">Total</flux:text>
                        <flux:text class="font-medium">€{{ number_format($confirmedTotal, 2) }}</flux:text>
                    </div>
                </flux:card>

                <flux:button href="{{ route('home') }}" variant="ghost" class="w-full">
                    Back home
                </flux:button>
            </div>
        </div>

    @else
        <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
            <div class="mx-auto max-w-3xl px-6 py-16 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400 mx-auto mb-5">
                    <flux:icon.home variant="outline" class="size-6" />
                </div>

                <flux:heading size="xl" level="1" class="text-3xl! sm:text-4xl! font-bold! leading-tight!">
                    Book your stay
                </flux:heading>

                <flux:text size="lg" class="mt-3 text-zinc-500 dark:text-zinc-400">
                    Enter your dates and we'll show you what's available.
                </flux:text>
            </div>
        </div>

        @if ($step === 'search')
            <div class="mx-auto max-w-xl px-6 py-14">
                <form wire:submit.prevent="search" class="space-y-6">
                    <div class="space-y-4">
                        <flux:heading size="sm" class="text-zinc-400 uppercase tracking-widest text-xs!">
                            When are you staying?
                        </flux:heading>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="check_in"
                                type="date"
                                label="Check-in"
                                :min="today()"
                            />
                            <flux:input
                                wire:model="check_out"
                                type="date"
                                label="Check-out"
                                :min="$check_in ?: today()"
                            />
                        </div>

                        @error('check_in')  <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                        @error('check_out') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror

                        <flux:input
                            wire:model="guests"
                            type="number"
                            label="Number of guests"
                            min="1"
                            placeholder="2"
                        />
                        @error('guests') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove>Find accommodation</span>
                        <span wire:loading>Searching…</span>
                    </flux:button>
                </form>
            </div>

        @elseif ($step === 'results')
            <div class="mx-auto max-w-3xl px-6 py-10 space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Available accommodation</flux:heading>
                        <flux:text class="text-zinc-500 text-sm mt-1">
                            {{ Carbon::parse($check_in)->format('d M') }} – {{ Carbon::parse($check_out)->format('d M Y') }}
                            · {{ $guests }} {{ $guests === 1 ? 'guest' : 'guests' }}
                        </flux:text>
                    </div>
                    <flux:button wire:click="backToSearch" variant="ghost" size="sm">
                        Change search
                    </flux:button>
                </div>

                @if ($this->availableTypes->isEmpty())
                    <div class="rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700 px-6 py-10 text-center">
                        <flux:text class="text-zinc-400">No accommodation available for your search. Try different dates or fewer guests.</flux:text>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($this->availableTypes as $type)
                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <flux:heading size="md">{{ $type->name }}</flux:heading>
                                        @if ($type->description)
                                            <flux:text class="text-zinc-500 text-sm">{{ $type->description }}</flux:text>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        @if ($type->base_price)
                                            <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">€{{ number_format($type->base_price, 2) }}</p>
                                            <flux:text class="text-xs text-zinc-400">per night</flux:text>
                                        @endif
                                    </div>
                                </div>

                                @if (!empty($type->amenities))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($type->amenities as $amenity)
                                            <flux:badge size="sm">
                                                {{ $amenity }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex items-center justify-between pt-1">
                                    <flux:text class="text-xs text-zinc-400">
                                        Up to {{ $type->max_guests }} {{ $type->max_guests === 1 ? 'guest' : 'guests' }}
                                    </flux:text>
                                    <flux:button wire:click="selectType({{ $type->id }})" variant="primary" size="sm">
                                        Select
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        @elseif ($step === 'book')
            <div class="mx-auto max-w-xl px-6 py-10">
                <form wire:submit.prevent="book" class="space-y-8">

                    @if ($this->selectedType)
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <flux:heading size="md">{{ $this->selectedType->name }}</flux:heading>
                                    @if ($this->selectedType->description)
                                        <flux:text class="text-zinc-500 text-sm">{{ $this->selectedType->description }}</flux:text>
                                    @endif
                                </div>
                                <flux:button wire:click="backToResults" variant="ghost" size="sm">
                                    Change
                                </flux:button>
                            </div>

                            @if (!empty($this->selectedType->amenities))
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($this->selectedType->amenities as $amenity)
                                        <flux:badge size="sm">
                                            {{ $amenity }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex justify-between text-sm pt-1 border-t border-zinc-100 dark:border-zinc-800">
                                <flux:text class="text-zinc-500">
                                    {{ Carbon::parse($check_in)->format('d M') }} – {{ Carbon::parse($check_out)->format('d M Y') }}
                                    · {{ $this->nights }} {{ $this->nights === 1 ? 'night' : 'nights' }}
                                    · {{ $guests }} {{ $guests === 1 ? 'guest' : 'guests' }}
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    @error('accommodation_type_id')
                        <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                    @enderror

                    @if ($this->extras->isNotEmpty())
                        <div class="space-y-3">
                            <flux:heading size="sm" class="text-zinc-400 uppercase tracking-widest text-xs!">
                                Add extras
                            </flux:heading>

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($this->extras as $extra)
                                    <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 cursor-pointer hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedExtras"
                                            value="{{ $extra->id }}"
                                            class="rounded border-zinc-300"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $extra->label }}</p>
                                            <p class="text-xs text-zinc-500">
                                                @if ($extra->amount_type === 'percentage')
                                                    {{ $extra->amount }}%
                                                @else
                                                    €{{ number_format($extra->amount, 2) }}
                                                @endif
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <flux:separator />
                    @endif

                    <div class="space-y-4">
                        <flux:heading size="sm" class="text-zinc-400 uppercase tracking-widest text-xs!">
                            Your details
                        </flux:heading>

                        <flux:input
                            wire:model="guest_name"
                            label="Full name"
                            placeholder="Jane Smith"
                        />
                        @error('guest_name') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror

                        <flux:input
                            wire:model="guest_email"
                            type="email"
                            label="Email address"
                            placeholder="jane@example.com"
                            description="We'll send your confirmation here."
                        />
                        @error('guest_email') <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text> @enderror

                        <flux:input
                            wire:model="guest_phone"
                            label="Phone (optional)"
                            placeholder="+31 6 12345678"
                        />

                        <flux:textarea
                            wire:model="special_requests"
                            label="Special requests (optional)"
                            placeholder="Allergies, accessibility needs, late arrival…"
                            rows="3"
                        />
                    </div>

                    @if ($this->nights > 0 && $accommodation_type_id)
                        <flux:separator />

                        <div class="space-y-3">
                            <flux:heading size="sm" class="text-zinc-400 uppercase tracking-widest text-xs!">
                                Price summary
                            </flux:heading>

                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($this->priceBreakdown['lines'] as $line)
                                    <div class="flex justify-between px-4 py-2.5 text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">{{ $line['label'] }}</span>
                                        <span class="font-medium">€{{ number_format($line['amount'], 2) }}</span>
                                    </div>
                                @endforeach

                                <div class="flex justify-between px-4 py-3 text-sm font-semibold bg-zinc-50 dark:bg-zinc-800/50">
                                    <span>Total</span>
                                    <span>€{{ number_format($this->priceBreakdown['total'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove>Confirm booking</span>
                        <span wire:loading>Booking…</span>
                    </flux:button>
                </form>
            </div>
        @endif
    @endif
</div>
