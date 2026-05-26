<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public array $roles = [
        'it',
        'manager',
        'receptionist',
        'restaurant_staff',
        'server',
        'housekeeper',
        'maintenance',
    ];

    public array $permissions = [
        'view_roles',
        'add_roles',
        'edit_roles',
        'delete_roles',
    ];

    public array $rolePermissions = [
        [
            'role_id' => 1, // IT
            'permission_id' => 1, // view_roles
        ],
        [
            'role_id' => 4,
            'permission_id' => 2,
        ],
        [
            'role_id' => 4,
            'permission_id' => 3,
        ],
        [
            'role_id' => 4,
            'permission_id' => 4,
        ]
    ];

    public array $staffRoles = [
        ['staff_id' => 1,  'role_id' => 1],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('roles')->insert(
            array_map(fn ($name) => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ], $this->roles)
        );

        DB::table('permissions')->insert(
            array_map(fn ($name) => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ], $this->permissions)
        );

        DB::table('role_permissions')->insert(
            array_map(fn ($rp) => [
                'role_id' => $rp['role_id'],
                'permission_id' => $rp['permission_id'],
            ], $this->rolePermissions)
        );

        DB::table('staff_roles')->insert(
            array_map(fn ($sr) => [
                'staff_id' => $sr['staff_id'],
                'role_id' => $sr['role_id'],
            ], $this->staffRoles)
        );
    }
}
