<flux:modal :name="$modalName">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Delete {{ $itemName }}?</flux:heading>

            <flux:text class="mt-2">
                You're about to delete this {{ strtolower($itemName) }}.<br>
                This action cannot be reversed.
            </flux:text>
        </div>

        <div class="flex gap-2">
            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>

            <flux:button
                type="button"
                variant="danger"
                wire:click="delete"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Delete {{ $itemName }}</span>
                <span wire:loading>Deleting...</span>
            </flux:button>
        </div>
    </div>
</flux:modal>

