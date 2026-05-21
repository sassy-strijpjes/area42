<flux:table :paginate="$this->staff">
    <flux:table.columns>
        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')"
                           width="50%">
            Name
        </flux:table.column>

        <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')"
                           width="20%">
            Email
        </flux:table.column>

        <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')">
            Role
        </flux:table.column>

        <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                           wire:click="sort('created_at')">
            Created
        </flux:table.column>

        <flux:table.column></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($this->staff as $member)
            <flux:table.row :key="$member->id">

                <flux:table.cell class="font-medium">
                    {{ $member->name }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $member->email }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm">
                        {{ $member->role_name === 'it' ? strtoupper($member->role_name) : ucfirst(str_replace('_', ' ', $member->role_name)) }}
                    </flux:badge>
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">
                    {{ \Carbon\Carbon::parse($member->created_at)->format('D F jS Y g:i A') }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                     inset="top bottom"></flux:button>
                        <flux:menu>
                            <flux:menu.item icon="pencil-square"
                                            :href="route('admin.staff.edit', $member->id)">Edit
                            </flux:menu.item>
                            <flux:menu.separator/>
                            <flux:menu.item
                                variant="danger"
                                icon="trash"
                                x-on:click="$flux.modal('delete-staff-{{ $member->id }}').show()"
                            >
                                Delete
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <livewire:modals.delete-confirmation
                        :key="'delete-' . $member->id"
                        :modalName="'delete-staff-' . $member->id"
                        :itemName="$member->name"
                        table="staff"
                        :itemId="$member->id"
                    />
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
