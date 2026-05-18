<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255|unique:roles,name')]
    public string $name = '';

    public array $selectedPermissions = [];

    #[Computed]
    public function groupedPermissions()
    {
        return DB::table('permissions')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                return last(explode('_', $permission->name));
            });
    }

    public function create(): void
    {
        $this->validate();

        DB::beginTransaction();

        try {
            $roleId = DB::table('roles')->insertGetId([
                'name' => $this->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! empty($this->selectedPermissions)) {
                $permissionData = array_map(function ($permissionId) use ($roleId) {
                    return [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }, $this->selectedPermissions);

                DB::table('role_permissions')->insert($permissionData);
            }

            DB::commit();

            Flux::toast('Role created successfully', variant: 'success');

            $this->redirect(route('admin.roles'), navigate: true);
        } catch (Exception) {
            DB::rollBack();

            Flux::toast('Error creating role', variant: 'error');
        }
    }
};
