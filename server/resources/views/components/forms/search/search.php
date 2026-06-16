<?php

use Livewire\Component;

new class extends Component
{
    public string $placeholder;

    public string $event;

    public ?string $size = null;

    public function mount(string $placeholder, string $event, ?string $size = null): void
    {
        $this->placeholder = $placeholder;
        $this->event = $event;
        $this->size = $size;
    }
};
