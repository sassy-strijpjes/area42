<x-layout.admin
    pageTitle="Logs"
    :user="user()"
>
    <x-slot:headerActions>
        <flux:input class="max-w-xs" placeholder="Search for a log..." icon:trailing="magnifying-glass" />
    </x-slot:headerActions>

    <flux:table>
        <flux:table.columns>
            <flux:table.column width="75%">Action</flux:table.column>
            <flux:table.column>Causer</flux:table.column>
            <flux:table.column>Time</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            <flux:table.row>
                <flux:table.cell>Added a new menu item</flux:table.cell>
                <flux:table.cell variant="strong">John Doe</flux:table.cell>
                <flux:table.cell>{{ \Carbon\Carbon::parse(now())->format('d/m/Y H:i:s') }}</flux:table.cell>
                <flux:table.cell>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        inset="top bottom"
                        class="!text-red-400 hover:!text-red-400"
                    />
                </flux:table.cell>
            </flux:table.row>

            <flux:table.row>
                <flux:table.cell>Added a new menu item</flux:table.cell>
                <flux:table.cell variant="strong">John Doe</flux:table.cell>
                <flux:table.cell>{{ \Carbon\Carbon::parse(now())->format('d/m/Y H:i:s') }}</flux:table.cell>
                <flux:table.cell>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        inset="top bottom"
                        class="!text-red-400 hover:!text-red-400"
                    />
                </flux:table.cell>
            </flux:table.row>

            <flux:table.row>
                <flux:table.cell>Added a new menu item</flux:table.cell>
                <flux:table.cell variant="strong">John Doe</flux:table.cell>
                <flux:table.cell>{{ \Carbon\Carbon::parse(now())->format('d/m/Y H:i:s') }}</flux:table.cell>
                <flux:table.cell>
                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        inset="top bottom"
                        class="!text-red-400 hover:!text-red-400"
                    />
                </flux:table.cell>
            </flux:table.row>
        </flux:table.rows>
    </flux:table>
</x-layout.admin>
