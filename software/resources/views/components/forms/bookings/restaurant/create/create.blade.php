<div>
    <form wire:submit.prevent="create">
        <div class="space-y-6">

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input
                    wire:model="guest_name"
                    label="Guest name"
                    placeholder="e.g. John Smith"
                />

                <flux:input
                    wire:model="guest_phone"
                    label="Phone (optional)"
                    placeholder="+31 6 12345678"
                />
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input
                    wire:model="booking_date"
                    type="date"
                    label="Date"
                />

                <flux:input
                    wire:model="booking_time"
                    type="time"
                    label="Start"
                />

                <flux:input
                    wire:model="booking_end_time"
                    type="time"
                    label="End (optional)"
                />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="table_id" label="Select table">
                    <option value="">Choose a table</option>

                    @foreach($this->tables() as $table)
                        <option value="{{ $table->id }}">
                            {{ $table->name }} ({{ $table->capacity }} seats)
                        </option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="party_size"
                    type="number"
                    label="Party size"
                    placeholder="e.g. 2, 4, 6..."
                />
            </div>

            <flux:textarea
                wire:model="notes"
                label="Notes (optional)"
                placeholder="Allergies, birthday, high chair, special requests..."
            />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    Create
                </flux:button>
            </div>

        </div>
    </form>

    <flux:modal name="confirm" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">
                This time slot is not available
            </flux:heading>

            <flux:text>
                If you continue, this booking will be added to the waitlist.
            </flux:text>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="primary"
                    wire:click="confirmWaitlist"
                >
                    Move to waitlist
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
