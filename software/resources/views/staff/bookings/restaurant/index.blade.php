<x-layout.staff
    pageTitle="Restaurant bookings"
    :user="user()"
>
    <x-slot:headerActions>
        <livewire:forms.search
            placeholder="Search restaurant bookings..."
            event="restaurant-bookings-search"
            size="sm"
        />

        @can('add_restaurant-bookings')
            <flux:button :href="route('staff.roles.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.bookings.restaurant />
</x-layout.staff>
