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
        'view_staff',
        'add_staff',
        'edit_staff',
        'delete_staff',
        'view_roles',
        'add_roles',
        'edit_roles',
        'delete_roles',
        'view_restaurant-bookings',
        'add_restaurant-bookings',
        'edit_restaurant-bookings',
        'cancel_restaurant-bookings',
        'view_accommodation-bookings',
        'add_accommodation-bookings',
        'edit_accommodation-bookings',
        'delete_accommodation-bookings',
        'view_accommodation-types',
        'add_accommodation-types',
        'edit_accommodation-types',
        'delete_accommodation-types',
        'view_accommodation-units',
        'add_accommodation-units',
        'edit_accommodation-units',
        'delete_accommodation-units',
        'view_accommodation-pricing-rules',
        'add_accommodation-pricing-rules',
        'edit_accommodation-pricing-rules',
        'delete_accommodation-pricing-rules',
        'view_ai_predictions',
        'train_ai_model',
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
        ],
        [
            'role_id' => 4,
            'permission_id' => 9,
        ],
        [
            'role_id' => 4,
            'permission_id' => 10,
        ],
        [
            'role_id' => 4,
            'permission_id' => 11,
        ],
        [
            'role_id' => 4,
            'permission_id' => 12,
        ],
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

        // Assign AI permissions: IT (role 1) gets both, Manager (role 2) gets view only
        $itRoleId = 1;   // IT
        $mgrRoleId = 2;  // Manager

        $viewAiId = DB::table('permissions')->where('name', 'view_ai_predictions')->value('id');
        $trainAiId = DB::table('permissions')->where('name', 'train_ai_model')->value('id');

        $aiAssignments = [];
        if ($viewAiId) {
            $aiAssignments[] = ['role_id' => $itRoleId, 'permission_id' => $viewAiId];
            $aiAssignments[] = ['role_id' => $mgrRoleId, 'permission_id' => $viewAiId];
        }
        if ($trainAiId) {
            $aiAssignments[] = ['role_id' => $itRoleId, 'permission_id' => $trainAiId];
        }

        foreach ($aiAssignments as $assignment) {
            DB::table('role_permissions')->insertOrIgnore($assignment);
        }
    }
}
