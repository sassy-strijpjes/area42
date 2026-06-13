<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('restaurant_tables')->insert([
            ['name' => 'Table 1', 'capacity' => 2, 'location' => 'Window', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 2', 'capacity' => 4, 'location' => 'Center', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 3', 'capacity' => 4, 'location' => 'Corner', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 4', 'capacity' => 6, 'location' => 'Center', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 5', 'capacity' => 8, 'location' => 'Back', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $today = Carbon::now()->startOfDay();

        DB::table('table_bookings')->insert([
            [
                'table_id' => 1,
                'guest_name' => 'John Smith',
                'guest_phone' => '555-0101',

                'booking_date' => $today->toDateString(),
                'booking_start' => '12:00',
                'booking_end' => '13:30',

                'party_size' => 2,
                'notes' => 'Window seating preferred',
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'table_id' => 2,
                'guest_name' => 'Maria Garcia',
                'guest_phone' => '555-0102',

                'booking_date' => $today->toDateString(),
                'booking_start' => '19:00',
                'booking_end' => '20:30',

                'party_size' => 4,
                'notes' => 'Anniversary dinner',
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}

