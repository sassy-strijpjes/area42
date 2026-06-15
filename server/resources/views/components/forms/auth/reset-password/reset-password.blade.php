<form wire:submit.prevent="resetPassword">
    <div class="flex flex-col gap-6">
        <flux:input wire:model="email" label="Email" type="email" placeholder="email@example.com" />
        <flux:input wire:model="password" label="New password" type="password" placeholder="New password" />
        <flux:input wire:model="password_confirmation" label="Confirm password" type="password" placeholder="Confirm password" />
        <flux:button type="submit" variant="primary" class="w-full cursor-pointer">Reset password</flux:button>
    </div>
</form>
