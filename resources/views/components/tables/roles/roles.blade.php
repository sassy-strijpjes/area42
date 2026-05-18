<flux:table :paginate="$this->roles">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')" width="50%">
            Name
        </flux:table.column>

        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">
            Created
        </flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($this->roles as $role)
            <flux:table.row :key="$role->id">

                <flux:table.cell class="font-medium">
                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">
                     {{ \Carbon\Carbon::parse($role->created_at)->format('D F jS Y g:i A') }}
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
