<form wire:submit.prevent="create">
    <div class="flex flex-col gap-6">

        <flux:input
            wire:model="name"
            label="Name"
            type="text"
            placeholder="E.g. receptionist"
        />

        <div>
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-3">
                Permissions
            </label>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @forelse($this->groupedPermissions as $group => $permissions)
                    <div class="border border-stone-200 dark:border-stone-700 rounded-lg p-3">

                        <div class="text-xs font-semibold text-stone-500 mb-2 uppercase">
                            {{ $group }}
                        </div>

                        <div class="flex flex-col gap-2">
                            @foreach($permissions as $permission)
                                <flux:field variant="inline" class="flex items-center gap-2">
                                    <flux:checkbox
                                        wire:model.live="selectedPermissions"
                                        value="{{ $permission->id }}"
                                        id="permission_{{ $permission->id }}"
                                    />

                                    <flux:label>
                                        {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                    </flux:label>
                                </flux:field>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500 col-span-3">
                        No permissions available
                    </p>
                @endforelse
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">
                Create
            </flux:button>
        </div>

    </div>
</form>
