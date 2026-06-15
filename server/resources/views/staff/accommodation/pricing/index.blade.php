<x-layout.staff
    pageTitle="Accommodation pricing rules"
    :user="user()"
>
    <x-slot:headerActions>
        <livewire:forms.search
            placeholder="Search for accommodation pricing rules..."
            event="accommodation-pricing-search"
            size="sm"
        />

        @can('add_accommodation-pricing-rules')
            <flux:button :href="route('staff.accommodation.pricing.create')" icon:trailing="plus" size="sm">
                Add
            </flux:button>
        @endcan
    </x-slot:headerActions>

    <livewire:tables.accommodation.pricing />
</x-layout.staff>
