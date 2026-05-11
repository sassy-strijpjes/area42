<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function can(int $userId, string $permission): bool
    {
        return DB::table('user_roles')
            ->join(
                'role_permissions',
                'user_roles.role_id',
                '=',
                'role_permissions.role_id'
            )
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'role_permissions.permission_id'
            )
            ->where('user_roles.user_id', $userId)
            ->where('permissions.name', $permission)
            ->exists();
    }
}
