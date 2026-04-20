<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_submit_verification_request_with_id(): void
    {
        Storage::fake('public');

        $tenant = User::factory()->create(['role' => 'Tenant']);
        TenantProfile::create([
            'user_id' => $tenant->user_id,
            'full_name' => 'Tenant Sample',
            'contact_number' => '09171234567',
            'contact_address' => '123 Sample Street, Quezon City',
        ]);

        $this->actingAs($tenant)
            ->post(route('verification.store'), [
                'id_document' => UploadedFile::fake()->image('gov-id.png'),
            ])
            ->assertRedirect();

        $profile = TenantProfile::where('user_id', $tenant->user_id)->first();

        $this->assertNotNull($profile);
        $this->assertSame(TenantProfile::VERIFICATION_PENDING, $profile->verification_status);
        $this->assertNotNull($profile->verification_submitted_at);
        $this->assertNotNull($profile->id_doc_url);

        Storage::disk('public')->assertExists($profile->id_doc_url);
    }

    public function test_rejected_tenant_can_resubmit_verification_request(): void
    {
        Storage::fake('public');

        $tenant = User::factory()->create(['role' => 'Tenant']);
        TenantProfile::create([
            'user_id' => $tenant->user_id,
            'full_name' => 'Rejected Tenant',
            'contact_number' => '09178888888',
            'contact_address' => '456 Sample Avenue, Quezon City',
            'verification_status' => TenantProfile::VERIFICATION_REJECTED,
            'verification_note' => 'Please upload a clearer photo.',
        ]);

        $this->actingAs($tenant)
            ->post(route('verification.store'), [
                'id_document' => UploadedFile::fake()->image('new-gov-id.png'),
            ])
            ->assertRedirect();

        $profile = TenantProfile::where('user_id', $tenant->user_id)->firstOrFail();

        $this->assertSame(TenantProfile::VERIFICATION_PENDING, $profile->verification_status);
        $this->assertNull($profile->verification_note);
        $this->assertNotNull($profile->verification_submitted_at);
        Storage::disk('public')->assertExists($profile->id_doc_url);
    }

    public function test_unverified_tenant_cannot_request_room(): void
    {
        $tenant = User::factory()->create(['role' => 'Tenant']);
        TenantProfile::create([
            'user_id' => $tenant->user_id,
            'full_name' => 'Unverified Tenant',
            'contact_number' => '09170000000',
        ]);

        $room = Room::create([
            'room_number' => 'B-101',
            'category' => 'Standard',
            'price_monthly' => 3000,
            'capacity' => 2,
            'status' => 'Available',
        ]);

        $this->actingAs($tenant)
            ->post(route('rooms.request', $room))
            ->assertSessionHas('error', 'Please complete tenant verification before requesting a room.');

        $this->assertDatabaseMissing('room_requests', [
            'user_id' => $tenant->user_id,
            'room_id' => $room->room_id,
        ]);
    }

    public function test_approved_tenant_can_request_room(): void
    {
        $tenant = User::factory()->create(['role' => 'Tenant']);
        TenantProfile::create([
            'user_id' => $tenant->user_id,
            'full_name' => 'Verified Tenant',
            'contact_number' => '09171111111',
            'verification_status' => TenantProfile::VERIFICATION_APPROVED,
            'verified_at' => now(),
        ]);

        $room = Room::create([
            'room_number' => 'B-102',
            'category' => 'Standard',
            'price_monthly' => 3200,
            'capacity' => 2,
            'status' => 'Available',
        ]);

        $this->actingAs($tenant)
            ->post(route('rooms.request', $room))
            ->assertSessionHas('success', 'Room request submitted successfully.');

        $this->assertDatabaseHas('room_requests', [
            'user_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
        ]);
    }
}
