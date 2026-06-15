<x-layout.staff
    pageTitle="Restaurant bookings"
    :user="user()"
>
    <x-slot:headerActions>
        @can('add_restaurant-bookings')
            <flux:button :href="route('staff.restaurant.bookings.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.bookings.restaurant />
</x-layout.staff>
