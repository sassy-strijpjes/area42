<flux:table>
    <flux:table.columns>
        <flux:table.column
            sortable
            :sorted="$sortBy === 'name'"
            :direction="$sortDirection"
            wire:click="sort('name')"
            width="30%"
        >
            Type
        </flux:table.column>
        <flux:table.column>Base price</flux:table.column>
        <flux:table.column>Rate bands</flux:table.column>
        <flux:table.column>Surcharges</flux:table.column>
        <flux:table.column>Extras</flux:table.column>
        <flux:table.column></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @forelse($this->rows as $row)
            <flux:table.row :key="$row->id">

                <flux:table.cell class="font-medium">
                    {{ $row->name }}
                </flux:table.cell>

                <flux:table.cell>
                    @if($row->has_pricing)
                        €{{ number_format($row->base_price, 2) }}
                    @else
                        Not set
                    @endif
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" variant="outline">{{ $row->band_count }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" variant="outline">{{ $row->surcharge_count }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" variant="outline">{{ $row->extras_count }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                        <flux:menu>
                            <flux:menu.item
                                icon="pencil-square"
                                :href="route('staff.accommodation.pricing.edit', $row->id)"
                            >
                                Edit
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item
                                variant="danger"
                                icon="trash"
                                x-on:click="$flux.modal('delete-pricing-{{ $row->id }}').show()"
                            >
                                Delete
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <flux:modal name="delete-pricing-{{ $row->id }}" class="max-w-md">
                        <div class="space-y-6">
                            <div>
                                <flux:heading size="lg">Delete pricing for {{ $row->name }}?</flux:heading>
                                <flux:text class="mt-2">
                                    This will delete the base price, all rate bands, surcharges, and extras for this type. This action cannot be reversed.
                                </flux:text>
                            </div>
                            <div class="flex gap-2">
                                <flux:spacer />
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button
                                    variant="danger"
                                    wire:click="delete({{ $row->id }})"
                                    x-on:click="$flux.modal('delete-pricing-{{ $row->id }}').close()"
                                >
                                    Delete
                                </flux:button>
                            </div>
                        </div>
                    </flux:modal>
                </flux:table.cell>

            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="6" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:heading size="sm">No accommodation types found</flux:heading>
                        <flux:text class="max-w-sm">
                            Add an accommodation type first before configuring pricing.
                        </flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
