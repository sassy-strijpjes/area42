<form wire:submit="login">
    <div class="flex flex-col gap-6">
        <flux:input wire:model="email" label="Email" type="email" placeholder="email@example.com" />

        <flux:field>
            <div class="mb-3 flex justify-between">
                <flux:label>Password</flux:label>

                <flux:link href="#" variant="subtle" class="text-sm">Forgot password?</flux:link>
            </div>

            <flux:input wire:model="password" type="password" placeholder="Your password" />
        </flux:field>

        <flux:checkbox wire:model="remember_me" label="Remember me" />

        <flux:button type="submit" variant="primary" class="w-full cursor-pointer">Log in</flux:button>
    </div>
</form>
