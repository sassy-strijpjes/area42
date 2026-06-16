<flux:table :paginate="$this->admins">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')" width="40%">
            Name
        </flux:table.column>

        <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')" width="40%">
            Email
        </flux:table.column>

        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">
            Created
        </flux:table.column>

        <flux:table.column></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @forelse ($this->admins as $admin)
            <flux:table.row :key="$admin->id">
                <flux:table.cell class="font-medium">
                    {{ $admin->name }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $admin->email }}
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">
                    {{ \Carbon\Carbon::parse($admin->created_at)->format('D F jS Y g:i A') }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                        <flux:menu>
                            <flux:menu.item icon="pencil-square" :href="route('admin.admins.edit', $admin->id)">
                                Edit
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item
                                variant="danger"
                                icon="trash"
                                x-on:click="$flux.modal('delete-admin-{{ $admin->id }}').show()"
                            >
                                Delete
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <livewire:modals.delete-confirmation
                        :key="'delete-' . $admin->id"
                        :modalName="'delete-admin-' . $admin->id"
                        :itemName="$admin->name"
                        table="admins"
                        :itemId="$admin->id"
                    />
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="4" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:heading size="sm">No administrators found</flux:heading>
                        <flux:text class="max-w-sm">
                            Try adjusting your search or add a new administrator.
                        </flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
