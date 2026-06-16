@props(['pageTitle' => 'Dashboard', 'user' => null])
<x-layout.master class="h-screen">
    <div class="flex flex-col lg:flex-row h-screen">
        <flux:sidebar sticky collapsible="mobile" class="lg:h-full bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.header>
                <flux:sidebar.brand
                        href="#"
                        logo="https://fluxui.dev/img/demo/logo.png"
                        name="Area42"
                />

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item
                    icon="home"
                    :href="route('admin.dashboard')"
                    :current="request()->routeIs('admin.dashboard')"
                    wire:navigate
                >
                    Dashboard
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="user-group"
                    :href="route('admin.admins')"
                    :current="request()->routeIs('admin.admins*')"
                    wire:navigate
                >
                    Administrators
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="users"
                    :href="route('admin.staff')"
                    :current="request()->routeIs('admin.staff*')"
                    wire:navigate
                >
                    Staff
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="shield-check"
                    :href="route('admin.roles')"
                    :current="request()->routeIs('admin.roles*')"
                    wire:navigate
                >
                    Roles
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="document-text"
                    :href="route('admin.logs')"
                    :current="request()->routeIs('admin.logs')"
                    wire:navigate
                >
                    Logs
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile avatar="{{ asset('img/user-icon.png') }}" :name="$user->name" />

                <flux:menu>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:profile avatar="{{ asset('img/user-icon.png') }}" />
        </flux:header>

        <flux:main class="flex-1 overflow-auto">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="xl">{{ $pageTitle }}</flux:heading>

                @isset($headerActions)
                    <div class="shrink-0">
                        {{ $headerActions }}
                    </div>
                @endisset
            </div>

            <flux:separator variant="subtle" class="my-8" />

            {{ $slot }}
        </flux:main>
    </div>
</x-layout.master>
