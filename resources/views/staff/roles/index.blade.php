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

        <flux:button :href="route('staff.roles.create')" icon:trailing="plus" size="sm">
            Add
        </flux:button>
    </x-slot:headerActions>

    <livewire:tables.roles />
</x-layout.staff>
