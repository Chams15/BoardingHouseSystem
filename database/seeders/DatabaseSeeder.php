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

        $tenants = [
            [
                'email' => 'juan.dela.cruz@tenant.test',
                'full_name' => 'Juan Dela Cruz',
                'contact_number' => '09171234567',
                'emergency_contact' => 'Maria Dela Cruz - 09179876543',
            ],
            [
                'email' => 'anna.santos@tenant.test',
                'full_name' => 'Anna Santos',
                'contact_number' => '09181234567',
                'emergency_contact' => 'Ramon Santos - 09177654321',
            ],
            [
                'email' => 'mark.reyes@tenant.test',
                'full_name' => 'Mark Reyes',
                'contact_number' => '09221234567',
                'emergency_contact' => 'Liza Reyes - 09229876543',
            ],
            [
                'email' => 'carla.mendoza@tenant.test',
                'full_name' => 'Carla Mendoza',
                'contact_number' => '09331234567',
                'emergency_contact' => 'Joel Mendoza - 09339876543',
            ],
            [
                'email' => 'leo.garcia@tenant.test',
                'full_name' => 'Leo Garcia',
                'contact_number' => '09441234567',
                'emergency_contact' => 'Grace Garcia - 09449876543',
            ],
        ];

        foreach ($tenants as $tenant) {
            $user = User::create([
                'role' => 'Tenant',
                'email' => $tenant['email'],
                'password' => Hash::make('tenant123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->tenantProfile()->create([
                'full_name' => $tenant['full_name'],
                'contact_number' => $tenant['contact_number'],
                'id_doc_url' => null,
                'emergency_contact' => $tenant['emergency_contact'],
            ]);
        }

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
