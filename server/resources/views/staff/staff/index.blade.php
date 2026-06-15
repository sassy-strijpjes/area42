<x-layout.staff
    pageTitle="Staff"
    :user="user()"
>
    <x-slot:headerActions>
        <livewire:forms.search
            placeholder="Search for a staff member..."
            event="staff-search"
            size="sm"
        />

        @can('add_staff')
            <flux:button :href="route('staff.staff.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.staff />
</x-layout.staff>
