<x-layout.staff
    pageTitle="Roles"
    :user="user()"
>
    <x-slot:headerActions>
        <livewire:forms.search
            placeholder="Search roles..."
            event="roles-search"
            size="sm"
        />

        @can('create_roles')
            <flux:button :href="route('staff.roles.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.roles />
</x-layout.staff>
