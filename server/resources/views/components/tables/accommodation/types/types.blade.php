<flux:table :paginate="$this->types">
    <flux:table.columns>
        <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
                width="30%"
        >
            Name
        </flux:table.column>

        <flux:table.column width="35%">
            Description
        </flux:table.column>

        <flux:table.column
                sortable
                :sorted="$sortBy === 'max_guests'"
                :direction="$sortDirection"
                wire:click="sort('max_guests')"
        >
            Max guests
        </flux:table.column>

        <flux:table.column>
            Amenities
        </flux:table.column>

        <flux:table.column></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @forelse ($this->types as $type)
            <flux:table.row :key="$type->id">

                <flux:table.cell class="font-medium">
                    {{ $type->name }}
                </flux:table.cell>

                <flux:table.cell class="text-zinc-500 dark:text-zinc-400">
                    {{ $type->description ?? 'No description' }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $type->max_guests }}
                </flux:table.cell>

                <flux:table.cell>
                    @php $amenities = json_decode($type->amenities ?? '[]') @endphp
                    @if(count($amenities))
                        <div class="flex flex-wrap gap-1">
                            @foreach($amenities as $amenity)
                                <flux:badge size="sm" variant="outline">{{ $amenity }}</flux:badge>
                            @endforeach
                        </div>
                    @else
                        <span class="text-zinc-400">—</span>
                    @endif
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                        <flux:menu>
                            <flux:menu.item icon="pencil-square" :href="route('staff.accommodation.types.edit', $type->id)">
                                Edit
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item
                                    variant="danger"
                                    icon="trash"
                                    x-on:click="$flux.modal('delete-type-{{ $type->id }}').show()"
                            >
                                Delete
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <livewire:modals.delete-confirmation
                            :key="'delete-type-' . $type->id"
                            :modalName="'delete-type-' . $type->id"
                            :itemName="$type->name"
                            table="accommodation_types"
                            :itemId="$type->id"
                    />
                </flux:table.cell>

            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="5" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:heading size="sm">No accommodation types found</flux:heading>
                        <flux:text class="max-w-sm">
                            Try adjusting your search or add a new type.
                        </flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
