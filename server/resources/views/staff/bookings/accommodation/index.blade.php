<x-layout.staff
    pageTitle="Accommodation bookings"
    :user="user()"
>
    <x-slot:headerActions>
        <livewire:forms.search
            placeholder="Search accommodation bookings..."
            event="accommodation-booking-search"
            size="sm"
        />

        @can('add_accommodation-bookings')
            <flux:button :href="route('staff.accommodation.bookings.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.bookings.accommodation />
</x-layout.staff>
