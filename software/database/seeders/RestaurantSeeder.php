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
            ['name' => 'Table 1', 'capacity' => 2, 'location' => 'Window', 'status' => 'available', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 2', 'capacity' => 4, 'location' => 'Center', 'status' => 'available', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 3', 'capacity' => 4, 'location' => 'Corner', 'status' => 'available', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 4', 'capacity' => 6, 'location' => 'Center', 'status' => 'available', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Table 5', 'capacity' => 8, 'location' => 'Back', 'status' => 'available', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $today = Carbon::now()->startOfDay();

        DB::table('table_bookings')->insert([
            [
                'table_id' => 1,
                'guest_name' => 'John Smith',
                'guest_phone' => '555-0101',
                'booking_start' => $today->clone()->setTime(12, 0),
                'booking_end' => $today->clone()->setTime(13, 30),
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
                'booking_start' => $today->clone()->setTime(19, 0),
                'booking_end' => $today->clone()->setTime(20, 30),
                'party_size' => 4,
                'notes' => 'Anniversary dinner, wine pairing',
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'table_id' => 4,
                'guest_name' => 'Robert Johnson',
                'guest_phone' => '555-0103',
                'booking_start' => $today->clone()->addDay()->setTime(18, 30),
                'booking_end' => $today->clone()->addDay()->setTime(20, 0),
                'party_size' => 6,
                'notes' => 'Business meeting',
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'table_id' => 3,
                'guest_name' => 'Emma Wilson',
                'guest_phone' => '555-0104',
                'booking_start' => $today->clone()->addDays(2)->setTime(13, 0),
                'booking_end' => $today->clone()->addDays(2)->setTime(14, 30),
                'party_size' => 4,
                'notes' => 'Lunch meeting',
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'table_id' => 5,
                'guest_name' => 'David Brown',
                'guest_phone' => '555-0105',
                'booking_start' => $today->clone()->addDays(3)->setTime(19, 30),
                'booking_end' => $today->clone()->addDays(3)->setTime(21, 0),
                'party_size' => 8,
                'notes' => 'Family celebration',
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}

