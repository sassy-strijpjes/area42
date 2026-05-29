<div>
    <flux:table :paginate="$this->roles">
        <flux:table.columns>
            <flux:table.column width="75%" sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                               wire:click="sort('name')">
                Name
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                               wire:click="sort('created_at')">
                Created
            </flux:table.column>
            @if(can('edit_roles') && can('delete_roles'))
                <flux:table.column></flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->roles as $role)
                <flux:table.row :key="$role->id">
                    <flux:table.cell class="font-medium">
                        {{ $role->name === 'it' ? strtoupper($role->name) : ucfirst(str_replace('_', ' ', $role->name)) }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($role->created_at)->format('D F jS Y g:i A') }}
                    </flux:table.cell>
                    @if(can('edit_roles') && can('delete_roles'))
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                                             inset="top bottom"></flux:button>
                                <flux:menu>
                                    @can('edit_roles')
                                        <flux:menu.item icon="pencil-square"
                                                        :href="route($prefix . '.roles.edit', $role->id)">Edit
                                        </flux:menu.item>
                                    @endcan
                                    @can('delete_roles')
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            variant="danger"
                                            icon="trash"
                                            x-on:click="$flux.modal('delete-role-{{ $role->id }}').show()"
                                        >
                                            Delete
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>

                            @can('delete_roles')
                                <livewire:modals.delete-confirmation
                                    :key="'delete-' . $role->id"
                                    :modalName="'delete-role-' . $role->id"
                                    :itemName="$role->name"
                                    table="roles"
                                    :itemId="$role->id"
                                />
                            @endcan
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
