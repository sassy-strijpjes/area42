<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'receptionist', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'restaurant_staff', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'server', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'manager', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'housekeeper', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'maintenance', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'it', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('permissions')->insert([
            // TODO
        ]);

        DB::table('role_permissions')->insert([
            // TODO
        ]);
    }
}
