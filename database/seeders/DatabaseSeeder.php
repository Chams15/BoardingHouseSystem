<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'role' => 'Admin',
            'email' => 'admin@boardinghouse.test',
            'password' => Hash::make('admin123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Room::insert([
            [
                'room_number' => '101',
                'category' => 'Single',
                'price_monthly' => 3000.00,
                'capacity' => 1,
                'status' => 'Available',
                'amenities' => 'Wi-Fi, Bed, Desk, Cabinet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_number' => '102',
                'category' => 'Single',
                'price_monthly' => 3000.00,
                'capacity' => 1,
                'status' => 'Available',
                'amenities' => 'Wi-Fi, Bed, Desk, Cabinet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_number' => '201',
                'category' => 'Shared',
                'price_monthly' => 2000.00,
                'capacity' => 2,
                'status' => 'Available',
                'amenities' => 'Wi-Fi, Bunk Bed, Shared Desk',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_number' => '202',
                'category' => 'Shared',
                'price_monthly' => 2000.00,
                'capacity' => 2,
                'status' => 'Occupied',
                'amenities' => 'Wi-Fi, Bunk Bed, Shared Desk',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_number' => '301',
                'category' => 'Premium',
                'price_monthly' => 5000.00,
                'capacity' => 1,
                'status' => 'Occupied',
                'amenities' => 'Wi-Fi, Queen Bed, Desk, AC, Private Bathroom',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
