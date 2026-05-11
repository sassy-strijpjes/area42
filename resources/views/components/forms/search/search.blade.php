
<flux:input
    class="max-w-xs"
    placeholder="{{ $placeholder ?? 'Search...' }}"
    icon:trailing="magnifying-glass"
    wire:keydown.debounce.300ms="dispatch('{{ $event }}', $event.target.value)"
/>
