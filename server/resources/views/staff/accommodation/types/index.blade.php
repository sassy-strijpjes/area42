<x-layout.staff
    pageTitle="Accommodation types"
    :user="user()"
>
    <x-slot:headerActions>
        <livewire:forms.search
            placeholder="Search for an accommodation type..."
            event="accommodation-type-search"
            size="sm"
        />

        @can('add_accommodation-types')
            <flux:button :href="route('staff.accommodation.types.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.accommodation.types />
</x-layout.staff>
