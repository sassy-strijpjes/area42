<form wire:submit.prevent="update">
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input
                    wire:model="name"
                    label="Name"
            />

            <flux:input
                    wire:model="max_guests"
                    type="number"
                    label="Max guests"
            />
        </div>

        <flux:textarea
                wire:model="description"
                label="Description (optional)"
        />

        <div
            x-data="{
                items: [],
                input: '',
                init() {
                    const raw = $wire.amenities;
                    if (raw) this.items = raw.split(',').map(a => a.trim()).filter(Boolean);
                },
                add() {
                    const val = this.input.trim();
                    if (val && !this.items.includes(val)) this.items.push(val);
                    this.input = '';
                    this.sync();
                },
                remove(i) {
                    this.items.splice(i, 1);
                    this.sync();
                },
                sync() {
                    $wire.amenities = this.items.join(', ');
                }
            }"
        >
            <flux:label>Amenities (optional)</flux:label>

            <div class="mt-2 flex gap-2">
                <flux:input
                        x-model="input"
                        placeholder="e.g. WiFi"
                        @keydown.enter.prevent="add"
                />
                <flux:button type="button" @click="add">Add</flux:button>
            </div>

            <div class="mt-3 flex flex-wrap gap-2" x-show="items.length">
                <template x-for="(item, i) in items" :key="i">
                    <flux:badge size="sm">
                        <span x-text="item"></span>
                        <button type="button" @click="remove(i)" class="ml-1 opacity-60 hover:opacity-100">✕</button>
                    </flux:badge>
                </template>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">
                Update
            </flux:button>
        </div>

    </div>
</form>
