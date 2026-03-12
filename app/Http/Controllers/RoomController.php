<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $rooms = Room::withCount(['roomRequests' => fn ($q) => $q->where('status', 'Pending')])
            ->get();

        $userRequests = RoomRequest::where('user_id', $request->user()->user_id)
            ->where('status', 'Pending')
            ->pluck('room_id')
            ->toArray();

        return Inertia::render('rooms/index', [
            'rooms' => $rooms,
            'userPendingRequests' => $userRequests,
        ]);
    }

    public function requestRoom(Request $request, Room $room): RedirectResponse
    {
        $user = $request->user();

        if ($room->status !== 'Available') {
            return back()->with('error', 'This room is not available.');
        }

        $existing = RoomRequest::where('user_id', $user->user_id)
            ->where('room_id', $room->room_id)
            ->where('status', 'Pending')
            ->exists();

        if ($existing) {
            return back()->with('error', 'You already have a pending request for this room.');
        }

        RoomRequest::create([
            'user_id' => $user->user_id,
            'room_id' => $room->room_id,
            'message' => $request->input('message'),
        ]);

        return back()->with('success', 'Room request submitted successfully.');
    }
}
