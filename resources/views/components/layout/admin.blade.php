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
                <flux:sidebar.item icon="home" href="#" current>Dashboard</flux:sidebar.item>
                <flux:sidebar.item icon="document-text" href="#">Logs</flux:sidebar.item>
                <flux:sidebar.item icon="users" href="#">Staff</flux:sidebar.item>
                <flux:sidebar.item icon="document-check" href="#">Audits</flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile avatar="https://fluxui.dev/img/demo/teej.png" name="John Doe" />

                <flux:menu>
                    <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:profile avatar="/img/demo/user.png" />
        </flux:header>

        <flux:main class="flex-1 overflow-auto">
            <flux:heading size="xl">Settings</flux:heading>

            <flux:separator variant="subtle" class="my-8" />

            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam assumenda culpa dolorem ea error, exercitationem facere illum itaque natus necessitatibus nisi officia provident quae quo sequi vitae voluptate. Architecto, quam!</p>
        </flux:main>
    </div>
</x-layout.master>
