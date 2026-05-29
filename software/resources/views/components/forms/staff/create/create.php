<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public string $prefix;

    public $roles;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:staff,email')]
    public string $email = '';

    #[Validate('required|confirmed|string|min:8|max:255')]
    public string $password = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password_confirmation = '';

    #[Validate('required|integer|exists:roles,id')]
    public int $role;

    public function mount()
    {
        $this->prefix = request()->routeIs('admin.*') ? 'admin' : 'staff';
        $this->roles = DB::table("roles")
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function create(): void
    {
        $this->validate();

        DB::beginTransaction();

        try {
            $staffId = DB::table("staff")->insertGetId([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            DB::table("staff_roles")->insert([
                'staff_id' => $staffId,
                'role_id' => $this->role,
            ]);

            DB::commit();

            Flux::toast('Staff member added successfully', variant: 'success');

            $this->redirect(route("{$this->prefix}.staff"), navigate: true);
        } catch (Exception) {
            DB::rollBack();

            Flux::toast('Error creating staff member', variant: 'error');
        }
    }
};
