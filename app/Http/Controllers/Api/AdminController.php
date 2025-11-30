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
        $today = today();

        // Count passengers - users with passenger role (using Spatie Permission scope)
        $passengers = User::role('passenger')->count();

        // Count drivers - count all driver profiles (all statuses)
        $drivers = DriverProfile::count();

        // Count active rides - rides that are requested, assigned, accepted, or in progress
        $activeRides = Ride::whereIn('status', [
            Ride::STATUS_REQUESTED,
            Ride::STATUS_ASSIGNED,
            Ride::STATUS_ACCEPTED,
            Ride::STATUS_IN_PROGRESS,
        ])->count();

        // Today's revenue - sum of fares from completed rides today
        $todayRevenue = Ride::where('status', Ride::STATUS_COMPLETED)
            ->whereDate('completed_at', $today)
            ->sum('fare');

        // Return simple format matching frontend requirements (Option 1: Direct Response)
        return response()->json([
            'passengers' => $passengers,
            'drivers' => $drivers,
            'active_rides' => $activeRides,
            'today_revenue' => round($todayRevenue ?? 0, 2),
        ]);
    }

    public function drivers(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $drivers = DriverProfile::with('user:id,name,email,phone')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                })->orWhere('plate_number', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%");
            })
            ->orderByDesc('updated_at')
            ->paginate($request->input('per_page', 15));

        $drivers->getCollection()->transform(function ($driver) {
            return [
                'id' => $driver->id,
                'user_id' => $driver->user_id,
                'name' => $driver->user->name,
                'email' => $driver->user->email,
                'phone' => $driver->user->phone,
                'vehicle_type' => $driver->vehicle_type,
                'plate_number' => $driver->plate_number,
                'license_number' => $driver->license_number,
                'franchise_number' => $driver->franchise_number,
                'status' => $driver->status,
                'is_online' => $driver->is_online ?? false,
                'current_lat' => $driver->current_lat,
                'current_lng' => $driver->current_lng,
                'location_updated_at' => $driver->location_updated_at,
                'created_at' => $driver->created_at,
                'updated_at' => $driver->updated_at,
            ];
        });

        return response()->json($drivers);
    }

    public function showDriver(DriverProfile $driverProfile)
    {
        $driverProfile->load([
            'user:id,name,email,phone,created_at',
            'user.driverRides' => function ($query) {
                $query->select('id', 'driver_id', 'status', 'fare', 'completed_at', 'created_at')
                      ->latest()
                      ->limit(10);
            },
        ]);

        $stats = [
            'total_rides' => Ride::where('driver_id', $driverProfile->user_id)->count(),
            'completed_rides' => Ride::where('driver_id', $driverProfile->user_id)
                ->where('status', Ride::STATUS_COMPLETED)
                ->count(),
            'total_revenue' => Ride::where('driver_id', $driverProfile->user_id)
                ->where('status', Ride::STATUS_COMPLETED)
                ->sum('fare'),
            'active_rides' => Ride::where('driver_id', $driverProfile->user_id)
                ->whereIn('status', [Ride::STATUS_ASSIGNED, Ride::STATUS_ACCEPTED, Ride::STATUS_IN_PROGRESS])
                ->count(),
        ];

        return response()->json([
            'data' => [
                'id' => $driverProfile->id,
                'user_id' => $driverProfile->user_id,
                'name' => $driverProfile->user->name,
                'email' => $driverProfile->user->email,
                'phone' => $driverProfile->user->phone,
                'vehicle_type' => $driverProfile->vehicle_type,
                'plate_number' => $driverProfile->plate_number,
                'license_number' => $driverProfile->license_number,
                'franchise_number' => $driverProfile->franchise_number,
                'status' => $driverProfile->status,
                'is_online' => $driverProfile->is_online ?? false,
                'current_lat' => $driverProfile->current_lat,
                'current_lng' => $driverProfile->current_lng,
                'location_updated_at' => $driverProfile->location_updated_at,
                'document_path' => $driverProfile->document_path,
                'created_at' => $driverProfile->created_at,
                'updated_at' => $driverProfile->updated_at,
                'user_created_at' => $driverProfile->user->created_at,
                'stats' => $stats,
                'recent_rides' => $driverProfile->user->driverRides,
            ],
        ]);
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
        $request->validate([
            'status' => ['nullable', 'in:requested,assigned,accepted,in_progress,completed,canceled'],
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
            'passenger_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $rides = Ride::with([
            'passenger:id,name,email,phone',
            'driver:id,name,email,phone',
            'driver.driverProfile:user_id,vehicle_type,plate_number',
            'pickupPlace',
            'dropoffPlace',
        ])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('driver_id'), fn ($query) => $query->where('driver_id', $request->driver_id))
            ->when($request->filled('passenger_id'), fn ($query) => $query->where('passenger_id', $request->passenger_id))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json($rides);
    }

    public function showRide(Ride $ride)
    {
        $ride->load([
            'passenger:id,name,email,phone,created_at',
            'driver:id,name,email,phone,created_at',
            'driver.driverProfile:user_id,vehicle_type,plate_number,license_number',
            'pickupPlace',
            'dropoffPlace',
        ]);

        return response()->json([
            'data' => $ride,
        ]);
    }
}
