<?php

use Livewire\Component;

new class extends Component
{
    public string $placeholder;

    public string $event;

    public function mount(string $placeholder, string $event): void
    {
        $this->placeholder = $placeholder;
        $this->event = $event;
    }
};
