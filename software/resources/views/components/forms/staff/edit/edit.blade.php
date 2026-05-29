<form wire:submit.prevent="update">
    <div class="flex flex-col gap-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <flux:input
                wire:model="name"
                label="Name"
                type="text"
                placeholder="E.g. John Doe"
            />

            <flux:input
                wire:model="email"
                label="Email"
                type="email"
                placeholder="E.g. johndoe@area42.com"
            />
        </div>

        <flux:input
            wire:model="password"
            label="Password"
            type="password"
            placeholder="Leave blank to keep current password"
        />

        <flux:select
            wire:model.number="role"
            label="Role"
            placeholder="Choose a role..."
        >
            @foreach($roles as $r)
                <option value="{{ $r->id }}" @selected($r->id === $this->role)>
                    {{ $r->name === 'it' ? strtoupper($r->name) : ucfirst(str_replace('_', ' ', $r->name)) }}
                </option>
            @endforeach
        </flux:select>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">
                Update
            </flux:button>
        </div>

    </div>
</form>
