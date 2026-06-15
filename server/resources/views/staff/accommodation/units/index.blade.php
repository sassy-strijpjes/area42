<x-layout.staff
    pageTitle="Accommodation units"
    :user="user()"
>
    <x-slot:headerActions>
        <livewire:forms.search
            placeholder="Search for an accommodation unit..."
            event="accommodation-unit-search"
            size="sm"
        />

        @can('add_accommodation-units')
            <flux:button :href="route('staff.accommodation.units.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.accommodation.units />
</x-layout.staff>
