<form wire:submit.prevent="update">
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input
                    wire:model="name"
                    label="Name"
            />

            <flux:select wire:model="accommodation_type_id" label="Type">
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
        />

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">
                Update
            </flux:button>
        </div>

    </div>
</form>
