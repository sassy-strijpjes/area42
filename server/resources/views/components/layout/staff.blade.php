@props(['pageTitle' => 'Dashboard', 'user' => null])
<x-layout.master>
    <div>
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 flex items-center">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="Area 42" class="max-lg:hidden" />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item
                    icon="home"
                    :href="route('staff.dashboard')"
                    :current="request()->routeIs('staff.dashboard')"
                    wire:navigate
                >
                    Dashboard
                </flux:navbar.item>

                @can('view_staff')
                    <flux:navbar.item
                        icon="users"
                        :href="route('staff.staff')"
                        :current="request()->routeIs('staff.staff*')"
                        wire:navigate
                    >
                        Staff
                    </flux:navbar.item>
                @endcan

                @can('view_roles')
                    <flux:navbar.item
                        icon="shield-check"
                        :href="route('staff.roles')"
                        :current="request()->routeIs('staff.roles*')"
                        wire:navigate
                    >
                        Roles
                    </flux:navbar.item>
                @endcan

                @can('view_restaurant-bookings')
                    <flux:dropdown>
                        <flux:navbar.item
                            icon="calendar-days"
                            icon:trailing="chevron-down"
                            :current="request()->routeIs('staff.restaurant.bookings*')"
                            wire:navigate
                        >
                            Bookings
                        </flux:navbar.item>
                        <flux:navmenu>
                            <flux:navmenu.item href="{{ route('staff.restaurant.bookings') }}">Restaurant</flux:navmenu.item>
                            <flux:navmenu.item href="#">Accomodations</flux:navmenu.item>
                            <flux:navmenu.item href="#">Bikes</flux:navmenu.item>
                        </flux:navmenu>
                    </flux:dropdown>
                @endcan
            </flux:navbar>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile class="cursor-pointer" avatar="{{ asset('img/user-icon.png') }}" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <flux:avatar src="{{ asset('img/user-icon.png') }}" size="sm" class="shrink-0" />

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ $user->name }}</span>
                                    <span class="truncate text-xs">{{ $user->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form action="{{ route('staff.logout') }}" method="POST">
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            Log out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Mobile sidebar --}}
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="Area 42" />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav variant="outline">
                <flux:sidebar.group>
                    <flux:sidebar.item
                        icon="home"
                        :href="route('staff.dashboard')"
                        :current="request()->routeIs('staff.dashboard')"
                        wire:navigate
                    >
                        Dashboard
                    </flux:sidebar.item>

                    @can('view_roles')
                        <flux:navbar.item
                            icon="shield-check"
                            :href="route('staff.roles')"
                            :current="request()->routeIs('staff.roles*')"
                            wire:navigate
                        >
                            Roles
                        </flux:navbar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />
        </flux:sidebar>

        <div>
            <div class="sm:border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <div class="max-w-7xl px-6 sm:px-8 py-3 mx-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-2">

                    <div class="max-sm:hidden flex items-baseline gap-3">
                        <flux:heading size="lg" class="text-lg">
                            {{ $pageTitle }}
                        </flux:heading>
                    </div>

                    <flux:spacer />

                    @isset($headerActions)
                        <div class="flex items-center gap-3">
                            {{ $headerActions }}
                        </div>
                    @endisset

                </div>
            </div>

            <div class="max-w-7xl px-6 sm:px-8 py-6 mx-auto">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layout.master>
