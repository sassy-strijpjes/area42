<?php

use App\Services\PermissionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function panel(): string
{
    return request()->routeIs('admin.*')
        ? 'admin'
        : 'staff';
}

if (!function_exists('user')) {
    function user()
    {
        $type = panel();

        $id = session()->get("{$type}_id");

        if (!$id) {
            return null;
        }

        $table = $type === 'admin' ? 'admins' : 'staff';

        return DB::table($table)->where('id', $id)->first();
    }
}

if (!function_exists('isAdminPanel')) {
    function isAdminPanel(): bool
    {
        return panel() === 'admin';
    }
}

if (!function_exists('role')) {
    function role()
    {
        if (isAdminPanel()) {
            return null;
        }

        $staff = user();

        if (!$staff) {
            return null;
        }

        return DB::table('roles')
            ->join('staff_roles', 'roles.id', '=', 'staff_roles.role_id')
            ->where('staff_roles.staff_id', $staff->id)
            ->orderBy('roles.level')
            ->select('roles.*')
            ->first();
    }
}

if (!function_exists('roleLevel')) {
    function roleLevel(): ?int
    {
        if (isAdminPanel()) {
            return 0;
        }

        return role()?->level;
    }
}

if (!function_exists('can')) {
    function can($permission): bool
    {
        if (isAdminPanel()) {
            return true;
        }

        $user = user();

        if (!$user) {
            return false;
        }

        return app(PermissionService::class)
            ->can($user->id, $permission);
    }
}

if (!function_exists('convert')) {
    function convert(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return Carbon::parse($time)->format('H:i');
    }
}
