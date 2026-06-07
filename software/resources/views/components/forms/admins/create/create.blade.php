<div>
    <form wire:submit.prevent="create">
        <div class="flex flex-col gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <flux:input
                    wire:model="name"
                    label="Name"
                    type="text"
                    placeholder="E.g. John Doe"
                />

                <flux:input
                    wire:model="email"
                    label="Email"
                    type="email"
                    placeholder="E.g. johndoe@area42.com"
                />
            </div>

            <flux:input
                wire:model="password"
                label="Password"
                type="password"
                placeholder="Enter a password"
            />

            <flux:input
                wire:model="password_confirmation"
                label="Confirm password"
                type="password"
                placeholder="Re-enter the password for confirmation"
            />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    Create
                </flux:button>
            </div>
        </div>
    </form>
</div>
