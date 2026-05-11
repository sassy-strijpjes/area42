<?php

use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    #[Validate('required|email')]
    public string $email;

    #[Validate('required')]
    public string $password;

    public bool $remember_me;

    public function login()
    {
        $this->validate();
    }
};
