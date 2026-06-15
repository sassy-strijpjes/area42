<form wire:submit.prevent="create">
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input
                    wire:model="name"
                    label="Name"
                    placeholder="e.g. Bungalow B12"
            />

            <flux:select wire:model="accommodation_type_id" label="Type">
                <option value="">Choose a type</option>
                @foreach($this->types() as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:select wire:model="status" label="Status">
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="maintenance">Under maintenance</option>
        </flux:select>

        <flux:textarea
                wire:model="notes"
                label="Notes (optional)"
                placeholder="Any additional details about this unit..."
        />

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">
                Create
            </flux:button>
        </div>

    </div>
</form>
