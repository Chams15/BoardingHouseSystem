<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'totalTenants' => User::where('role', 'Tenant')->count(),
                'activeTenants' => User::where('role', 'Tenant')->where('is_active', true)->count(),
                'inactiveTenants' => User::where('role', 'Tenant')->where('is_active', false)->count(),
                'totalRooms' => Room::count(),
                'availableRooms' => Room::where('status', 'Available')->count(),
                'occupiedRooms' => Room::where('status', 'Occupied')->count(),
            ],
        ]);
    }
}
