<x-layout.master>
    <div class="flex min-h-screen w-full items-center justify-center">
        <div class="w-80 max-w-80 space-y-6">
            <div class="flex justify-center opacity-50">
                <a href="/" class="group flex items-center gap-3">
                    <span class="text-xl font-semibold text-zinc-800 dark:text-white">Area 42 {{ request()->routeIs('admin*') ? 'Admin' : 'Staff' }}</span>
                </a>
            </div>
            <flux:heading class="text-center" size="xl">Reset password</flux:heading>
            <livewire:forms.auth.reset-password :type="$type" :token="$token" :email="$email" />
        </div>
    </div>
</x-layout.master>
