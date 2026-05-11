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
            ['name' => 'receptionist'],
            ['name' => 'restaurant_staff'],
            ['name' => 'server'],
            ['name' => 'manager'],
            ['name' => 'housekeeper'],
            ['name' => 'maintenance'],
            ['name' => 'it'],
        ]);

        DB::table('permissions')->insert([
            // TODO
        ]);

        DB::table('role_permissions')->insert([
            // TODO
        ]);
    }
}
