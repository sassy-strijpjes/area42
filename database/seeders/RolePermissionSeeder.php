<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public array $roles = [
        'receptionist',
        'restaurant_staff',
        'server',
        'manager',
        'housekeeper',
        'maintenance',
        'it',
    ];

    public array $permissions = [
        'view_roles',
        'add_roles',
        'edit_roles',
        'delete_roles',
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

        DB::table('role_permissions')->insert([
            // TODO
        ]);
    }
}
