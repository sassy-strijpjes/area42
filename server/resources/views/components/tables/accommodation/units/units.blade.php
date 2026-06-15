<flux:table :paginate="$this->units">
    <flux:table.columns>
        <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
                width="25%"
        >
            Name
        </flux:table.column>

        <flux:table.column
                sortable
                :sorted="$sortBy === 'type_name'"
                :direction="$sortDirection"
                wire:click="sort('type_name')"
                width="25%"
        >
            Type
        </flux:table.column>

        <flux:table.column
                sortable
                :sorted="$sortBy === 'status'"
                :direction="$sortDirection"
                wire:click="sort('status')"
        >
            Status
        </flux:table.column>

        <flux:table.column>
            Active
        </flux:table.column>

        <flux:table.column width="30%">
            Notes
        </flux:table.column>

        <flux:table.column></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @forelse ($this->units as $unit)
            <flux:table.row :key="$unit->id">

                <flux:table.cell class="font-medium">
                    {{ $unit->name }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" variant="outline">{{ $unit->type_name }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    @php
                        $color = match($unit->status) {
                            'available'   => 'green',
                            'occupied'    => 'red',
                            'maintenance' => 'yellow',
                            default       => 'zinc',
                        };
                    @endphp
                    <flux:badge size="sm" :color="$color">
                        {{ ucfirst($unit->status) }}
                    </flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    @if($unit->is_active)
                        <flux:badge size="sm" color="green">Active</flux:badge>
                    @else
                        <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                    @endif
                </flux:table.cell>

                <flux:table.cell class="text-zinc-500 dark:text-zinc-400">
                    {{ $unit->notes ?? 'No notes' }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                        <flux:menu>
                            <flux:menu.item
                                    icon="pencil-square"
                                    :href="route('staff.accommodation.units.edit', $unit->id)"
                            >
                                Edit
                            </flux:menu.item>

                            @if(!$unit->is_active || $unit->status === 'available')
                                <flux:menu.item
                                        :icon="$unit->is_active ? 'eye-slash' : 'eye'"
                                        wire:click="toggleActive({{ $unit->id }})"
                                >
                                    {{ $unit->is_active ? 'Deactivate' : 'Activate' }}
                                </flux:menu.item>
                            @endif

                            <flux:menu.separator />

                            <flux:menu.item
                                    variant="danger"
                                    icon="trash"
                                    x-on:click="$flux.modal('delete-unit-{{ $unit->id }}').show()"
                            >
                                Delete
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <livewire:modals.delete-confirmation
                            :key="'delete-unit-' . $unit->id"
                            :modalName="'delete-unit-' . $unit->id"
                            :itemName="$unit->name"
                            table="accommodation_units"
                            :itemId="$unit->id"
                    />
                </flux:table.cell>

            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="6" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:heading size="sm">No units found</flux:heading>
                        <flux:text class="max-w-sm">
                            Try adjusting your search or add a new unit.
                        </flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
