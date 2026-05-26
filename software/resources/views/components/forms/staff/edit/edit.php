<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public $staff;

    public $roles;

    public int $staffId;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:staff,email')]
    public string $email = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password = '';

    #[Validate('required|integer|exists:roles,id')]
    public int $role;

    public function mount($staff)
    {
        $staffId = is_numeric($staff) ? $staff : ($staff->id ?? null);

        if (! $staffId) {
            return;
        }

        $this->staffId = $staffId;

        $staffData = DB::table('staff')->find($staffId);

        if ($staffData) {
            $this->staff = $staffData;
            $this->name = $staffData->name;
            $this->email = $staffData->email;

            $this->roles = DB::table("roles")
                ->select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            $currentRole = DB::table("staff_roles")
                ->where('staff_id', $staffId)
                ->first();

            if ($currentRole) {
                $this->role = $currentRole->role_id;
            }
        }
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:staff,email,'.$this->staff->id,
            'password' => 'nullable|string|min:8|max:255',
            'role' => 'required|integer|exists:roles,id'
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            if (!empty($this->password)) {
                $updateData['password'] = Hash::make($this->password);
            }

            DB::table("staff")
                ->where('id', $this->staff->id)
                ->update($updateData);

            DB::table("staff_roles")
                ->where('staff_id', $this->staff->id)
                ->update(['role_id' => $this->role]);

            DB::commit();

            Flux::toast('Staff member edited successfully', variant: 'success');

            $this->redirect(route("admin.staff"), navigate: true);
        } catch (Exception) {
            DB::rollBack();

            Flux::toast('Error editing staff member', variant: 'error');
        }
    }
};
