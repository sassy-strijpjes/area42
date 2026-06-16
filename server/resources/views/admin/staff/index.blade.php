<x-layout.admin
    pageTitle="Staff"
    :user="user()"
>
    <x-slot:headerActions>
        <div class="flex flex-row gap-3">
            <livewire:forms.search
                placeholder="Search for a staff member..."
                event="staff-search"
            />
            <flux:button :href="route('admin.staff.create')" icon:trailing="plus">Add</flux:button>
        </div>
    </x-slot:headerActions>
    <livewire:tables.staff />
</x-layout.admin>
