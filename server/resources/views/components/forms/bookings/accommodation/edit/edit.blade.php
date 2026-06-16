<div>
    <form wire:submit.prevent="update">
        <div class="space-y-8">
            <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input
                        wire:model="guest_name"
                        label="Full name"
                        placeholder="e.g. Jane Doe"
                    />
                    <flux:input
                        wire:model="guest_email"
                        type="email"
                        label="Email"
                        placeholder="jane@example.com"
                    />
                    <flux:input
                        wire:model="guest_phone"
                        label="Phone"
                        placeholder="+31 6 12345678"
                    />
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select wire:model.live="accommodation_type_id" label="Accommodation type">
                        <option value="">Choose a type</option>
                        @foreach($this->accommodationTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="guests"
                        type="number"
                        min="1"
                        label="Number of guests"
                        :max="$this->selectedType?->max_guests ?? 99"
                    />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input
                        wire:model.live="check_in"
                        type="date"
                        label="Check-in"
                    />
                    <flux:input
                        wire:model.live="check_out"
                        type="date"
                        label="Check-out"
                        :min="$check_in"
                    />
                </div>

                @if($accommodation_type_id && $this->nights > 0)
                    <flux:select wire:model="unit_id" label="Unit (optional)">
                        <option value="">Auto-assign</option>
                        @forelse($this->availableUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @empty
                            <option disabled>No available units for these dates</option>
                        @endforelse
                    </flux:select>
                @endif
            </div>

            @if($this->extras->isNotEmpty())
                <div class="space-y-3">
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($this->extras as $extra)
                            <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 cursor-pointer hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedExtras"
                                    value="{{ $extra->id }}"
                                    class="rounded border-zinc-300 text-primary-600"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $extra->label }}</p>
                                    <p class="text-xs text-zinc-500">
                                        @if($extra->amount_type === 'percentage')
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
            @endif

            @if($this->nights > 0 && $accommodation_type_id)
                <div class="space-y-3">
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->priceBreakdown['lines'] as $line)
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

            <div class="space-y-4">
                <flux:select wire:model="payment_status" label="Payment status">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                </flux:select>

                <flux:textarea
                    wire:model="special_requests"
                    label="Special requests (optional)"
                    placeholder="Allergies, accessibility needs, late arrival..."
                />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button :href="route('staff.accommodation.bookings')" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save changes
                </flux:button>
            </div>

        </div>
    </form>
</div>
