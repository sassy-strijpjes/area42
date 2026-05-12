<form wire:submit.prevent="sendResetLink">
    <div class="flex flex-col gap-6">
        <flux:input wire:model="email" label="Email" type="email" placeholder="email@example.com" />

        <flux:button type="submit" variant="primary" class="w-full cursor-pointer">Email password reset link</flux:button>
    </div>
</form>
