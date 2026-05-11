<x-layout.admin
    pageTitle="Logs"
    :user="user()"
>
    <x-slot:headerActions>
        <div class="flex flex-row gap-3">
            <flux:input class="max-w-xs" placeholder="Search for a log..." icon:trailing="magnifying-glass" />

            <flux:dropdown>
                <flux:button icon:trailing="chevron-down">Sort by</flux:button>
                <flux:menu>
                    <flux:menu.radio.group>
                        <flux:menu.radio checked value="recent">Recent</flux:menu.radio>
                        <flux:menu.radio value="oldest">Oldest</flux:menu.radio>
                        <flux:menu.radio value="az">A-Z (action)</flux:menu.radio>
                        <flux:menu.radio value="za">Z-A (action)</flux:menu.radio>
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </div>
    </x-slot:headerActions>

    <flux:table>
        <flux:table.columns>
            <flux:table.column width="75%">Action</flux:table.column>
            <flux:table.column>Causer</flux:table.column>
            <flux:table.column>Time</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            <flux:table.row>
                <flux:table.cell>Added a new menu item</flux:table.cell>
                <flux:table.cell variant="strong">John Doe</flux:table.cell>
                <flux:table.cell>{{ \Carbon\Carbon::parse(now())->format('d/m/Y H:i:s') }}</flux:table.cell>
            </flux:table.row>

            <flux:table.row>
                <flux:table.cell>Added a new menu item</flux:table.cell>
                <flux:table.cell variant="strong">John Doe</flux:table.cell>
                <flux:table.cell>{{ \Carbon\Carbon::parse(now())->format('d/m/Y H:i:s') }}</flux:table.cell>
            </flux:table.row>

            <flux:table.row>
                <flux:table.cell>Added a new menu item</flux:table.cell>
                <flux:table.cell variant="strong">John Doe</flux:table.cell>
                <flux:table.cell>{{ \Carbon\Carbon::parse(now())->format('d/m/Y H:i:s') }}</flux:table.cell>
            </flux:table.row>
        </flux:table.rows>
    </flux:table>
</x-layout.admin>
