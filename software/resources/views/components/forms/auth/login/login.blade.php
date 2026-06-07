<form wire:submit.prevent="login">
    <div class="flex flex-col gap-6">
        <flux:input wire:model="email" label="Email" type="email" placeholder="email@example.com" />

        <flux:field>
            <div class="mb-3 flex justify-between">
                <flux:label>Password</flux:label>

                <flux:link href="{{ $type == 'admin' ? route('admin.forgot-password') : route('staff.forgot-password') }}" variant="subtle" class="text-sm">Forgot password?</flux:link>
            </div>

            <flux:input wire:model="password" type="password" placeholder="Your password" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full cursor-pointer">Log in</flux:button>
    </div>
</form>
