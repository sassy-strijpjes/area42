<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public $admin;

    public int $adminId;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:admins,email')]
    public string $email = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password = '';

    public function mount($admin)
    {
        $adminId = is_numeric($admin) ? $admin : ($admin->id ?? null);

        if (! $adminId) {
            return;
        }

        $this->adminId = $adminId;

        $adminData = DB::table('admins')->find($adminId);

        if ($adminData) {
            $this->admin = $adminData;
            $this->name = $adminData->name;
            $this->email = $adminData->email;
        }
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins,email,'.$this->admin->id,
            'password' => 'nullable|string|min:8|max:255',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            if (! empty($this->password)) {
                $updateData['password'] = Hash::make($this->password);
            }

            DB::table('admins')
                ->where('id', $this->admin->id)
                ->update($updateData);

            DB::commit();

            Flux::toast('Administrator edited successfully', variant: 'success');

            $this->redirect(route('admin.admins'), navigate: true);
        } catch (Exception) {
            DB::rollBack();

            Flux::toast('Error editing administrator', variant: 'error');
        }
    }
};
