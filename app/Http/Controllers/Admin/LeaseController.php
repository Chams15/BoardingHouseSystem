<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaseController
{
    /**
     * Show the create lease form
     */
    public function create(Room $room): \Inertia\Response
    {
        return Inertia::render('admin/leases/create', [
            'room' => $room,
            'tenants' => User::where('role', 'Tenant')->get(),
        ]);
    }

    /**
     * Store a new lease contract
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:users,user_id',
            'room_id' => 'required|exists:rooms,room_id',
            'start_date' => 'required|date',
            'security_deposit' => 'nullable|numeric|min:0',
        ]);

        // Check if tenant already has an active lease for this room
        $existingLease = LeaseContract::where('tenant_id', $validated['tenant_id'])
            ->where('room_id', $validated['room_id'])
            ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
            ->first();

        if ($existingLease) {
            return back()->with('error', 'This tenant already has an active lease in this room.');
        }

        // Check if room already has an active lease
        $roomLease = LeaseContract::where('room_id', $validated['room_id'])
            ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
            ->first();

        if ($roomLease) {
            return back()->with('error', 'This room already has an active lease.');
        }

        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        
        LeaseContract::create([
            'tenant_id' => $validated['tenant_id'],
            'room_id' => $validated['room_id'],
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonth(),  // Monthly lease
            'security_deposit' => $validated['security_deposit'] ?? 0,
            'contract_status' => 'Active',
            'auto_renew' => true,  // Enable auto-renewal by default
            'next_renewal_date' => $startDate->copy()->addMonth(),  // Renew one month from start
        ]);

        // Update room status to Occupied
        Room::where('room_id', $validated['room_id'])
            ->update(['status' => 'Occupied']);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Monthly lease created successfully with auto-renewal enabled.');
    }

    /**
     * Show the edit lease form
     */
    public function edit(LeaseContract $lease): \Inertia\Response
    {
        return Inertia::render('admin/leases/edit', [
            'lease' => $lease->load(['tenant', 'room']),
            'tenants' => User::where('role', 'Tenant')->get(),
        ]);
    }

    /**
     * Update a lease contract
     */
    public function update(Request $request, LeaseContract $lease): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:users,user_id',
            'start_date' => 'required|date',
            'security_deposit' => 'nullable|numeric|min:0',
            'contract_status' => 'required|in:Active,Pending_MoveOut,Terminated',
            'auto_renew' => 'boolean',
        ]);

        // If changing tenant, check for duplicate active leases
        if ($validated['tenant_id'] != $lease->tenant_id) {
            $existingLease = LeaseContract::where('tenant_id', $validated['tenant_id'])
                ->where('room_id', $lease->room_id)
                ->where('contract_id', '!=', $lease->contract_id)
                ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
                ->first();

            if ($existingLease) {
                return back()->with('error', 'This tenant already has an active lease in this room.');
            }
        }

        $oldStatus = $lease->contract_status;
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        
        // Update auto-renewal setting
        $lease->auto_renew = $validated['auto_renew'] ?? true;
        
        // If we're terminating the lease, disable auto-renewal
        if ($validated['contract_status'] === 'Terminated') {
            $lease->auto_renew = false;
            $lease->next_renewal_date = null;
        } elseif ($validated['contract_status'] === 'Active' && !$lease->next_renewal_date) {
            // If reactivating without a renewal date, set it
            $lease->next_renewal_date = $startDate->copy()->addMonth();
        }
        
        $lease->update([
            'tenant_id' => $validated['tenant_id'],
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonth(),  // Keep end_date one month from start
            'security_deposit' => $validated['security_deposit'] ?? 0,
            'contract_status' => $validated['contract_status'],
            'auto_renew' => $lease->auto_renew,
            'next_renewal_date' => $lease->next_renewal_date,
        ]);

        // Update room status based on lease status
        if ($oldStatus !== 'Terminated' && $validated['contract_status'] === 'Terminated') {
            // Lease was terminated, mark room as available
            Room::where('room_id', $lease->room_id)
                ->update(['status' => 'Available']);
        } elseif ($oldStatus === 'Terminated' && $validated['contract_status'] !== 'Terminated') {
            // Lease was reactivated, mark room as occupied
            Room::where('room_id', $lease->room_id)
                ->update(['status' => 'Occupied']);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Lease updated successfully.');
    }

    /**
     * Delete/terminate a lease contract
     */
    public function destroy(LeaseContract $lease): RedirectResponse
    {
        $roomId = $lease->room_id;

        // Soft delete by terminating the contract
        $lease->update(['contract_status' => 'Terminated']);

        // Mark room as available
        Room::where('room_id', $roomId)
            ->update(['status' => 'Available']);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Lease terminated and room marked as available.');
    }

    /**
     * Hard delete a lease contract (use with caution)
     */
    public function hardDelete(LeaseContract $lease): RedirectResponse
    {
        $roomId = $lease->room_id;
        $lease->delete();

        // Mark room as available
        Room::where('room_id', $roomId)
            ->update(['status' => 'Available']);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Lease permanently deleted and room marked as available.');
    }
}
