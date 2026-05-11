@props(['pageTitle' => 'Dashboard', 'actionHref' => '#', 'actionText' => 'New', 'icon' => 'plus', 'user' => null])
<x-layout.master>
    <div>
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 flex items-center">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="Area 42" class="max-lg:hidden dark:hidden" />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item href="#">Accomodations</flux:navbar.item>
                <flux:dropdown>
                    <flux:navbar.item icon:trailing="chevron-down">Restaurant</flux:navbar.item>
                    <flux:navmenu>
                        <flux:navmenu.item href="#">Reservations</flux:navmenu.item>
                    </flux:navmenu>
                </flux:dropdown>
            </flux:navbar>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile class="cursor-pointer" avatar="https://fluxui.dev/img/demo/teej.png" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <flux:avatar src="https://fluxui.dev/img/demo/teej.png" size="sm" class="shrink-0" />

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ $user->name }}</span>
                                    <span class="truncate text-xs">{{ $user->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog">Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        Log out
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Mobile sidebar --}}
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand href="#" name="Podium">
                    <div class="flex aspect-square items-center justify-center rounded-md bg-accent text-accent-foreground p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mic-vocal">
                            <path d="m11 7.601-5.994 8.19a1 1 0 0 0 .1 1.298l.817.818a1 1 0 0 0 1.314.087L15.09 12" />
                            <path d="M16.5 21.174C15.5 20.5 14.372 20 13 20c-2.058 0-3.928 2.356-6 2-2.072-.356-2.775-3.369-1.5-4.5" />
                            <circle cx="16" cy="7" r="5" />
                        </svg>
                    </div>
                </flux:sidebar.brand>

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav variant="outline">
                <flux:sidebar.group>
                    <flux:sidebar.item href="#" current>
                        Questions
                    </flux:sidebar.item>

                    <flux:sidebar.item href="#">
                        Leaderboard
                    </flux:sidebar.item>

                    <flux:sidebar.item href="#">
                        Announcements
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />
        </flux:sidebar>

        <div>
            <div class="sm:border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <div class="max-w-7xl px-6 sm:px-8 py-3 mx-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-2">
                    <div class="max-sm:hidden flex items-baseline gap-3">
                        <flux:heading size="lg" class="text-lg">{{ $pageTitle }}</flux:heading>
                    </div>

                    <flux:spacer />

                    @if($actionHref && $actionText)
                        @if($icon)
                            <flux:button href="{{ $actionHref }}" color="primary" size="sm" :icon:trailing="$icon" class="flex items-center gap-1">
                                {{ $actionText }}
                            </flux:button>
                        @else
                            <flux:button href="{{ $actionHref }}" color="primary" size="sm" class="flex items-center gap-1">
                                {{ $actionText }}
                            </flux:button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="max-w-7xl px-6 sm:px-8 py-6 mx-auto">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layout.master>
