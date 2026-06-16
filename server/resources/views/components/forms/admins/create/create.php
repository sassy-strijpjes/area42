<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:admins,email')]
    public string $email = '';

    #[Validate('required|confirmed|string|min:8|max:255')]
    public string $password = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password_confirmation = '';

    public function create(): void
    {
        $this->validate();

        DB::beginTransaction();

        try {
            DB::table('admins')->insert([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            DB::commit();

            Flux::toast('Administrator added successfully', variant: 'success');

            $this->redirect(route('admin.admins'), navigate: true);
        } catch (Exception) {
            DB::rollBack();

            Flux::toast('Error creating administrator', variant: 'error');
        }
    }
};
