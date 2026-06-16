<x-layout.admin
    pageTitle="Administrators"
    :user="user()"
>
    <x-slot:headerActions>
        <div class="flex flex-row gap-3">
            <livewire:forms.search
                placeholder="Search for an administrator..."
                event="admins-search"
            />

            <flux:button :href="route('admin.admins.create')" icon:trailing="plus">Add</flux:button>
        </div>
    </x-slot:headerActions>

    <livewire:tables.admins />
</x-layout.admin>

