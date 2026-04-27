<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Blacklist;
use App\Models\LeaseContract;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\SecurityIncident;
use App\Models\TenantProfile;
use App\Models\User;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();
        $roomImagePath = 'rooms/8V5DgqQhQFocVOdDouFLUE4t0SQv2FMKIK9Bmhc2.jpg';

        // Clear scenario records so seeding is safe to run multiple times.
        Payment::query()->delete();
        Bill::query()->delete();
        RoomRequest::query()->delete();
        VisitorLog::query()->delete();
        MaintenanceTicket::query()->delete();
        SecurityIncident::query()->delete();
        LeaseContract::query()->delete();
        Blacklist::query()->delete();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@boardinghouse.test'],
            [
                'role' => 'Admin',
                'password' => Hash::make('admin123'),
                'is_active' => true,
                'email_verified_at' => $now,
            ]
        );

        $tenants = [
            [
                'email' => 'juan.dela.cruz@tenant.test',
                'full_name' => 'Juan Dela Cruz',
                'contact_number' => '09171234567',
                'emergency_contact' => 'Maria Dela Cruz - 09179876543',
                'contact_address' => 'Unit 1, Rizal St., Manila',
                'verification_status' => TenantProfile::VERIFICATION_APPROVED,
                'verification_note' => 'Valid government ID verified.',
                'verification_submitted_at' => Carbon::now()->subDays(20),
                'verified_at' => Carbon::now()->subDays(18),
            ],
            [
                'email' => 'anna.santos@tenant.test',
                'full_name' => 'Anna Santos',
                'contact_number' => '09181234567',
                'emergency_contact' => 'Ramon Santos - 09177654321',
                'contact_address' => 'Apt 4B, Quezon City',
                'verification_status' => TenantProfile::VERIFICATION_PENDING,
                'verification_note' => 'Awaiting clearer ID photo upload.',
                'verification_submitted_at' => Carbon::now()->subDays(2),
                'verified_at' => null,
            ],
            [
                'email' => 'mark.reyes@tenant.test',
                'full_name' => 'Mark Reyes',
                'contact_number' => '09221234567',
                'emergency_contact' => 'Liza Reyes - 09229876543',
                'contact_address' => 'Blk 8 Lot 2, Pasig City',
                'verification_status' => TenantProfile::VERIFICATION_REJECTED,
                'verification_note' => 'Submitted ID is expired. Re-upload required.',
                'verification_submitted_at' => Carbon::now()->subDays(10),
                'verified_at' => Carbon::now()->subDays(9),
            ],
            [
                'email' => 'carla.mendoza@tenant.test',
                'full_name' => 'Carla Mendoza',
                'contact_number' => '09331234567',
                'emergency_contact' => 'Joel Mendoza - 09339876543',
                'contact_address' => 'Purok 5, Taguig City',
                'verification_status' => TenantProfile::VERIFICATION_NOT_SUBMITTED,
                'verification_note' => null,
                'verification_submitted_at' => null,
                'verified_at' => null,
            ],
            [
                'email' => 'leo.garcia@tenant.test',
                'full_name' => 'Leo Garcia',
                'contact_number' => '09441234567',
                'emergency_contact' => 'Grace Garcia - 09449876543',
                'contact_address' => 'San Jose Village, Makati City',
                'verification_status' => TenantProfile::VERIFICATION_APPROVED,
                'verification_note' => 'All documents validated.',
                'verification_submitted_at' => Carbon::now()->subDays(30),
                'verified_at' => Carbon::now()->subDays(28),
            ],
        ];

        $tenantUsers = [];
        foreach ($tenants as $tenant) {
            $user = User::query()->updateOrCreate(
                ['email' => $tenant['email']],
                [
                    'role' => 'Tenant',
                    'password' => Hash::make('tenant123'),
                    'is_active' => true,
                    'email_verified_at' => $now,
                ]
            );

            TenantProfile::query()->updateOrCreate([
                'user_id' => $user->user_id,
            ], [
                'full_name' => $tenant['full_name'],
                'contact_number' => $tenant['contact_number'],
                'contact_address' => $tenant['contact_address'],
                'id_doc_url' => null,
                'emergency_contact' => $tenant['emergency_contact'],
                'verification_status' => $tenant['verification_status'],
                'verification_note' => $tenant['verification_note'],
                'verification_submitted_at' => $tenant['verification_submitted_at'],
                'verified_at' => $tenant['verified_at'],
                'verified_by' => in_array($tenant['verification_status'], [
                    TenantProfile::VERIFICATION_APPROVED,
                    TenantProfile::VERIFICATION_REJECTED,
                ], true) ? $admin->user_id : null,
            ]);

            $tenantUsers[$tenant['email']] = $user;
        }

        Room::query()->upsert([
            [
                'room_number' => '101',
                'category' => 'Single',
                'price_monthly' => 3000.00,
                'capacity' => 1,
                'status' => 'Available',
                'amenities' => 'Wi-Fi, Bed, Desk, Cabinet',
                'room_image_path' => $roomImagePath,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_number' => '102',
                'category' => 'Single',
                'price_monthly' => 3000.00,
                'capacity' => 1,
                'status' => 'Available',
                'amenities' => 'Wi-Fi, Bed, Desk, Cabinet',
                'room_image_path' => $roomImagePath,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_number' => '201',
                'category' => 'Shared',
                'price_monthly' => 2000.00,
                'capacity' => 2,
                'status' => 'Available',
                'amenities' => 'Wi-Fi, Bunk Bed, Shared Desk',
                'room_image_path' => $roomImagePath,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_number' => '202',
                'category' => 'Shared',
                'price_monthly' => 2000.00,
                'capacity' => 2,
                'status' => 'Occupied',
                'amenities' => 'Wi-Fi, Bunk Bed, Shared Desk',
                'room_image_path' => $roomImagePath,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_number' => '301',
                'category' => 'Premium',
                'price_monthly' => 5000.00,
                'capacity' => 1,
                'status' => 'Occupied',
                'amenities' => 'Wi-Fi, Queen Bed, Desk, AC, Private Bathroom',
                'room_image_path' => $roomImagePath,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_number' => '302',
                'category' => 'Single',
                'price_monthly' => 3200.00,
                'capacity' => 1,
                'status' => 'Occupied',
                'amenities' => 'Wi-Fi, Bed, Study Lamp, Cabinet',
                'room_image_path' => $roomImagePath,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_number' => '401',
                'category' => 'Premium',
                'price_monthly' => 5500.00,
                'capacity' => 1,
                'status' => 'Maintenance',
                'amenities' => 'Wi-Fi, Queen Bed, Desk, AC, Private Bathroom, Balcony',
                'room_image_path' => $roomImagePath,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['room_number'], [
            'category',
            'price_monthly',
            'capacity',
            'status',
            'amenities',
            'room_image_path',
            'updated_at',
        ]);

        $rooms = Room::query()->get()->keyBy('room_number');

        // Monthly leases with auto-renewal
        $contractActiveA = LeaseContract::create([
            'tenant_id' => $tenantUsers['juan.dela.cruz@tenant.test']->user_id,
            'room_id' => $rooms['202']->room_id,
            'start_date' => Carbon::now()->subMonths(4),
            'end_date' => Carbon::now()->subMonths(4)->addMonth(),
            'security_deposit' => 2000,
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => Carbon::now(),  // Due for renewal today
        ]);

        $contractActiveB = LeaseContract::create([
            'tenant_id' => $tenantUsers['anna.santos@tenant.test']->user_id,
            'room_id' => $rooms['301']->room_id,
            'start_date' => Carbon::now()->subDays(15),
            'end_date' => Carbon::now()->subDays(15)->addMonth(),
            'security_deposit' => 5000,
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => Carbon::now()->addDays(15),  // Renews in 15 days
        ]);

        $contractMoveOut = LeaseContract::create([
            'tenant_id' => $tenantUsers['mark.reyes@tenant.test']->user_id,
            'room_id' => $rooms['302']->room_id,
            'start_date' => Carbon::now()->subMonths(2),
            'end_date' => Carbon::now()->subMonths(2)->addMonth(),
            'security_deposit' => 3200,
            'contract_status' => 'Pending_MoveOut',
            'move_out_req_date' => Carbon::now()->subDays(5),
            'auto_renew' => false,
            'move_out_final_date' => Carbon::now()->addDays(3),  // Final move-out in 3 days
        ]);

        $contractTerminated = LeaseContract::create([
            'tenant_id' => $tenantUsers['carla.mendoza@tenant.test']->user_id,
            'room_id' => $rooms['201']->room_id,
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->subMonths(2),
            'security_deposit' => 2000,
            'contract_status' => 'Terminated',
            'move_out_req_date' => Carbon::now()->subMonths(2)->subDays(5),
            'auto_renew' => false,
            'next_renewal_date' => null,
        ]);

       
        MaintenanceTicket::insert([
            [
                'room_id' => $rooms['202']->room_id,
                'reported_by' => $tenantUsers['juan.dela.cruz@tenant.test']->user_id,
                'issue_desc' => 'Leaking faucet in bathroom sink.',
                'issue_photo_path' => null,
                'priority' => 'Medium',
                'status' => 'Pending',
                'contractor_notes' => null,
                'resolved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_id' => $rooms['301']->room_id,
                'reported_by' => $tenantUsers['anna.santos@tenant.test']->user_id,
                'issue_desc' => 'Air conditioning is not cooling properly.',
                'issue_photo_path' => 'maintenance/ac-issue-301.jpg',
                'priority' => 'High',
                'status' => 'In Progress',
                'contractor_notes' => 'Scheduled compressor inspection.',
                'resolved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'room_id' => null,
                'reported_by' => $tenantUsers['mark.reyes@tenant.test']->user_id,
                'issue_desc' => 'Second-floor hallway light flickers intermittently.',
                'issue_photo_path' => null,
                'priority' => 'Low',
                'status' => 'Resolved',
                'contractor_notes' => 'Replaced defective bulb and checked wiring.',
                'resolved_at' => Carbon::now()->subDays(3),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        VisitorLog::insert([
            [
                'tenant_visited' => $tenantUsers['juan.dela.cruz@tenant.test']->user_id,
                'visitor_name' => 'Paolo Dela Cruz',
                'visitor_photo_path' => 'visitors/paolo-dela-cruz.jpg',
                'purpose' => 'Family visit',
                'time_in' => Carbon::now()->subHours(6),
                'time_out' => Carbon::now()->subHours(4),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_visited' => $tenantUsers['anna.santos@tenant.test']->user_id,
                'visitor_name' => 'Janine Lopez',
                'visitor_photo_path' => null,
                'purpose' => 'Project collaboration',
                'time_in' => Carbon::now()->subMinutes(75),
                'time_out' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_visited' => $tenantUsers['leo.garcia@tenant.test']->user_id,
                'visitor_name' => 'Courier - LBC',
                'visitor_photo_path' => null,
                'purpose' => 'Package delivery',
                'time_in' => Carbon::now()->subDays(1)->setTime(10, 15),
                'time_out' => Carbon::now()->subDays(1)->setTime(10, 28),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        RoomRequest::insert([
            [
                'user_id' => $tenantUsers['leo.garcia@tenant.test']->user_id,
                'room_id' => $rooms['101']->room_id,
                'status' => 'Pending',
                'message' => 'Prefer a quiet room near the study area.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $tenantUsers['carla.mendoza@tenant.test']->user_id,
                'room_id' => $rooms['102']->room_id,
                'status' => 'Approved',
                'message' => 'Re-applying after previous contract ended.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $tenantUsers['mark.reyes@tenant.test']->user_id,
                'room_id' => $rooms['401']->room_id,
                'status' => 'Rejected',
                'message' => 'Interested in premium room if available.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        SecurityIncident::insert([
            [
                'reported_by' => $tenantUsers['juan.dela.cruz@tenant.test']->user_id,
                'title' => 'Unauthorized person in stairwell',
                'description' => 'Unknown individual was seen near restricted area around midnight.',
                'severity' => 'Medium',
                'status' => 'Open',
                'resolved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'reported_by' => $admin->user_id,
                'title' => 'Main gate lock malfunction',
                'description' => 'Gate lock intermittently fails to latch securely.',
                'severity' => 'High',
                'status' => 'Investigating',
                'resolved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'reported_by' => null,
                'title' => 'Noise complaint after curfew',
                'description' => 'Loud music reported from common area after midnight.',
                'severity' => 'Low',
                'status' => 'Resolved',
                'resolved_at' => Carbon::now()->subDays(7),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Blacklist::create([
            'email' => 'banned.applicant@tenant.test',
            'reason' => 'Repeated policy violations and fraudulent document submission.',
            'banned_at' => Carbon::now()->subMonths(1),
        ]);
    }
}
