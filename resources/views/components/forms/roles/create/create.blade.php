<form wire:submit.prevent="create">
    <div class="flex flex-col gap-6">
        <flux:input
            wire:model="name"
            label="Name"
            type="text"
            placeholder="E.g. receptionist"
        />

        <div class="flex justify-end">
            <flux:button
                type="submit"
                variant="primary"
                class="w-auto cursor-pointer"
            >
                Create
            </flux:button>
        </div>
    </div>
</form>
