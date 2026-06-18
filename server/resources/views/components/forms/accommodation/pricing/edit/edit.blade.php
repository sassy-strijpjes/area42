@php
    $activeExtras = array_filter($extras, fn($e) => $e['is_active'] && $e['label']);
@endphp

<div x-data="{
    nights: 3,
    bandPrice: null,
    selectedExtras: [],
    get basePrice() { return parseFloat('{{ $base_price ?: 0 }}') || 0; },
    get nightlyRate() { return this.bandPrice !== null ? this.bandPrice : this.basePrice; },
    get subtotal() { return this.nightlyRate * this.nights; },
    get total() {
        let surcharges = @js(collect($surcharges)->where('is_active', true)->where('type', 'weekend')->values());
        let extras = @js(collect($activeExtras)->values());
        let surchargeTotal = surcharges.reduce((sum, s) =>
            sum + (s.amount_type === 'percentage' ? this.subtotal * s.amount / 100 : parseFloat(s.amount)), 0);
        let extrasTotal = extras
            .filter(e => this.selectedExtras.includes(e.label))
            .reduce((sum, e) =>
                sum + (e.amount_type === 'percentage' ? this.subtotal * e.amount / 100 : parseFloat(e.amount)), 0);
        return this.subtotal + surchargeTotal + extrasTotal;
    },
    fmt(v) { return '€' + parseFloat(v || 0).toFixed(2); }
}">
    <form wire:submit.prevent="save">
        <div class="space-y-6">

            <flux:input
                wire:model="base_price"
                type="number"
                step="0.01"
                label="Base price per night"
                placeholder="0.00"
                prefix="€"
            />

            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">Seasonal rate bands</flux:heading>
                        <flux:subheading>Override the base price for specific date ranges.</flux:subheading>
                    </div>
                    <flux:button type="button" size="sm" icon="plus" wire:click="addRateBand">Add band</flux:button>
                </div>

                @forelse($rate_bands as $i => $band)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-4 space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:input wire:model="rate_bands.{{ $i }}.label" label="Label" placeholder="e.g. High season" />
                            <flux:select wire:model="rate_bands.{{ $i }}.season" label="Season">
                                <option value="high">High</option>
                                <option value="mid">Mid</option>
                                <option value="low">Low</option>
                            </flux:select>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <flux:input wire:model="rate_bands.{{ $i }}.start_date" type="date" label="From" />
                            <flux:input wire:model="rate_bands.{{ $i }}.end_date" type="date" label="To" />
                            <flux:input wire:model="rate_bands.{{ $i }}.price_per_night" type="number" step="0.01" label="Price per night" placeholder="0.00" prefix="€" />
                        </div>
                        <div class="flex justify-end">
                            <flux:button type="button" variant="ghost" size="sm" icon="trash" wire:click="removeRateBand({{ $i }})">Remove</flux:button>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-400">No rate bands configured yet.</flux:text>
                @endforelse
            </flux:card>

            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">Surcharges</flux:heading>
                        <flux:subheading>Extra charges applied automatically based on booking conditions.</flux:subheading>
                    </div>
                    <flux:button type="button" size="sm" icon="plus" wire:click="addSurcharge">Add surcharge</flux:button>
                </div>

                @forelse($surcharges as $i => $surcharge)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-4 space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:input wire:model="surcharges.{{ $i }}.label" label="Label" placeholder="e.g. Weekend surcharge" />
                            <flux:select wire:model="surcharges.{{ $i }}.type" label="Type">
                                <option value="weekend">Weekend</option>
                                <option value="last_minute">Last minute</option>
                                <option value="early_bird">Early bird</option>
                            </flux:select>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <flux:select wire:model="surcharges.{{ $i }}.amount_type" label="Amount type">
                                <option value="fixed">Fixed (€)</option>
                                <option value="percentage">Percentage (%)</option>
                            </flux:select>
                            <flux:input wire:model="surcharges.{{ $i }}.amount" type="number" step="0.01" label="Amount" placeholder="0.00" />
                            <flux:input wire:model="surcharges.{{ $i }}.days_threshold" type="number" label="Days threshold" placeholder="e.g. 7" />
                        </div>
                        <div class="flex items-center justify-between">
                            <flux:checkbox wire:model="surcharges.{{ $i }}.is_active" label="Active" />
                            <flux:button type="button" variant="ghost" size="sm" icon="trash" wire:click="removeSurcharge({{ $i }})">Remove</flux:button>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-400">No surcharges configured yet.</flux:text>
                @endforelse
            </flux:card>

            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">Optional extras</flux:heading>
                        <flux:subheading>Add-ons guests can select when booking.</flux:subheading>
                    </div>
                    <flux:button type="button" size="sm" icon="plus" wire:click="addExtra">Add extra</flux:button>
                </div>

                @forelse($extras as $i => $extra)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-600 p-4 space-y-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <flux:input wire:model="extras.{{ $i }}.label" label="Label" placeholder="e.g. Linens, BBQ, Cleaning" />
                            <flux:select wire:model="extras.{{ $i }}.amount_type" label="Amount type">
                                <option value="fixed">Fixed (€)</option>
                                <option value="percentage">Percentage (%)</option>
                            </flux:select>
                            <flux:input wire:model="extras.{{ $i }}.amount" type="number" step="0.01" label="Amount" placeholder="0.00" />
                        </div>
                        <div class="flex items-center justify-between">
                            <flux:checkbox wire:model="extras.{{ $i }}.is_active" label="Active" />
                            <flux:button type="button" variant="ghost" size="sm" icon="trash" wire:click="removeExtra({{ $i }})">Remove</flux:button>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-400">No extras configured yet.</flux:text>
                @endforelse
            </flux:card>

            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="sm">Price preview</flux:heading>
                    <flux:subheading>Estimate a total before saving. Does not affect saved data.</flux:subheading>
                </div>

                <div class="grid gap-4 {{ count($activeExtras) ? 'md:grid-cols-3' : 'md:grid-cols-2' }}">
                    <flux:input x-model.number="nights" type="number" min="1" label="Number of nights" placeholder="3" />

                    <flux:select label="Apply rate band" @change="bandPrice = $event.target.value ? parseFloat($event.target.value) : null">
                        <option value="">No band (base price)</option>
                        @foreach($rate_bands as $i => $band)
                            <option value="{{ $band['price_per_night'] }}">{{ $band['label'] ?: 'Band ' . ($i + 1) }}</option>
                        @endforeach
                    </flux:select>

                    @if(count($activeExtras))
                        <div>
                            <flux:label>Include extras</flux:label>
                            <div class="mt-2 space-y-2">
                                @foreach($activeExtras as $extra)
                                    <flux:checkbox
                                        :label="$extra['label']"
                                        value="{{ $extra['label'] }}"
                                        x-model="selectedExtras"
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4 space-y-2 text-sm">
                    <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                        <span x-text="`${nights} night(s) × ${fmt(nightlyRate)}`"></span>
                        <span x-text="fmt(subtotal)"></span>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-600 pt-2 font-semibold text-base">
                        <span>Estimated total</span>
                        <span x-text="fmt(total)"></span>
                    </div>
                </div>
            </flux:card>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Save pricing</flux:button>
            </div>

        </div>
    </form>
</div>
