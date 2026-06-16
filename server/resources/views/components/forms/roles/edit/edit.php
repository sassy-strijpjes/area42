<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $prefix;

    public int $roleId;

    public string $name = '';

    public array $selectedPermissions = [];

    public function mount($role = null)
    {
        $this->prefix = request()->routeIs('admin.*') ? 'admin' : 'staff';

        $roleId = is_numeric($role) ? $role : ($role->id ?? null);

        if (! $roleId) {
            return;
        }

        $this->roleId = $roleId;

        $roleData = DB::table('roles')->find($roleId);

        if ($roleData) {
            $this->name = $roleData->name;

            $permissions = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->toArray();

            $this->selectedPermissions = $permissions;
        }
    }

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

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$this->roleId,
        ]);

        DB::beginTransaction();
        try {
            DB::table('roles')
                ->where('id', $this->roleId)
                ->update([
                    'name' => $this->name,
                    'updated_at' => now(),
                ]);

            DB::table('role_permissions')
                ->where('role_id', $this->roleId)
                ->delete();

            if (! empty($this->selectedPermissions)) {
                $permissionData = array_map(function ($permissionId) {
                    return [
                        'role_id' => $this->roleId,
                        'permission_id' => $permissionId,
                    ];
                }, $this->selectedPermissions);

                DB::table('role_permissions')->insert($permissionData);
            }

            DB::commit();

            Flux::toast('Role updated successfully', variant: 'success');

            $this->redirect(route("{$this->prefix}.roles"), navigate: true);
        } catch (Exception) {
            DB::rollBack();

            Flux::toast('Error editing role', variant: 'error');
        }
    }
};
