<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public string $type = 'staff';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember_me = false;

    public function mount(?string $type = null): void
    {
        $this->type = in_array($type, ['admin', 'staff'], true) ? $type : 'staff';
    }

    public function login(): void
    {
        $this->validate();

        $table = $this->type == 'admin' ? 'users' : 'staff';

        $user = DB::table($table)
            ->where('email', $this->email)
            ->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->addError('email', __('auth.failed'));

            return;
        }

        session(["{$this->type}_id" => $user->id]);

        session()->regenerate();

        $route = $this->type === 'admin' ? route('admin.dashboard') : route('staff.dashboard');

        $this->redirectIntended($route);
    }
};
