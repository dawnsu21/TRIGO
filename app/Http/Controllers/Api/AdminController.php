<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'passengers' => User::role('passenger')->count(),
            'drivers'    => User::role('driver')->count(),
            'rides'      => Ride::count(),
            'active_rides' => Ride::whereIn('status', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_IN_PROGRESS,
            ])->count(),
        ];

        return response()->json([
            'message' => 'Admin dashboard overview.',
            'stats'   => $stats,
        ]);
    }

    public function drivers(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
        ]);

        $drivers = DriverProfile::with('user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('updated_at')
            ->paginate(15);

        return response()->json($drivers);
    }

    public function updateDriverStatus(Request $request, DriverProfile $driverProfile)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $driverProfile->update([
            'status' => $validated['status'],
        ]);

        if ($validated['status'] === DriverProfile::STATUS_REJECTED) {
            $driverProfile->update([
                'is_online' => false,
            ]);
        }

        return response()->json(['message' => 'Driver status updated.']);
    }

    public function rides()
    {
        $rides = Ride::with(['passenger:id,name', 'driver:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json($rides);
    }
}
