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
            'active_rides' => Ride::whereIn('status', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_IN_PROGRESS,
            ])->count(),
            'today_revenue' => Ride::where('status', Ride::STATUS_COMPLETED)
                ->whereDate('completed_at', today())
                ->sum('fare'),
            'pending_drivers' => DriverProfile::where('status', DriverProfile::STATUS_PENDING)->count(),
        ];

        return response()->json($stats);
    }

    //testingggg
    public function drivers(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
        ]);

        $drivers = DriverProfile::with('user:id,name,email,phone')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user->name,
                    'email' => $driver->user->email,
                    'phone' => $driver->user->phone,
                    'vehicle_type' => $driver->vehicle_type,
                    'plate_number' => $driver->plate_number,
                    'license_number' => $driver->license_number,
                    'status' => $driver->status,
                    'user' => [
                        'id' => $driver->user->id,
                        'name' => $driver->user->name,
                        'email' => $driver->user->email,
                    ],
                ];
            });

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

        // Refresh to get updated data
        $driverProfile->refresh();

        return response()->json([
            'message' => 'Driver status updated.',
            'driver' => [
                'id' => $driverProfile->id,
                'user_id' => $driverProfile->user_id,
                'status' => $driverProfile->status,
                'is_online' => $driverProfile->is_online,
                'user' => $driverProfile->user->only(['id', 'name', 'email']),
            ],
        ]);
    }

    public function rides(Request $request)
    {
        $rides = Ride::with([
            'passenger:id,name,email,phone',
            'driver:id,name,email,phone',
            'pickupPlace',
            'dropoffPlace',
        ])
            ->latest()
            ->paginate(20);

        return response()->json($rides);
    }
}
