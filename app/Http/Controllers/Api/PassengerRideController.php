<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Feedback;
use App\Models\Place;
use App\Models\Ride;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class PassengerRideController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $today = today();
        $thisMonth = now()->startOfMonth();

        // Stats
        $totalRides = Ride::where('passenger_id', $user->id)->count();
        $completedRides = Ride::where('passenger_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->count();
        
        $canceledRides = Ride::where('passenger_id', $user->id)
            ->where('status', Ride::STATUS_CANCELED)
            ->count();
        
        $todayRides = Ride::where('passenger_id', $user->id)
            ->whereDate('created_at', $today)
            ->count();
        
        $totalSpent = Ride::where('passenger_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->sum('fare');
        
        $thisMonthSpent = Ride::where('passenger_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$thisMonth, now()])
            ->sum('fare');

        // Active ride
        $activeRide = Ride::where('passenger_id', $user->id)
            ->whereIn('status', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_IN_PROGRESS,
            ])
            ->latest()
            ->with([
                'driver:id,name,email,phone',
                'driver.driverProfile:user_id,vehicle_type,plate_number',
                'pickupPlace',
                'dropoffPlace',
            ])
            ->first();

        // Determine if cancellation is available for active ride
        $canCancel = false;
        if ($activeRide) {
            $canCancel = in_array($activeRide->status, [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_ACCEPTED,
            ], true);
        }

        // Recent rides
        $recentRides = Ride::where('passenger_id', $user->id)
            ->with(['driver:id,name', 'pickupPlace', 'dropoffPlace'])
            ->latest()
            ->limit(5)
            ->get(['id', 'driver_id', 'status', 'fare', 'completed_at', 'created_at']);

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'stats' => [
                    'total_rides' => $totalRides,
                    'completed_rides' => $completedRides,
                    'canceled_rides' => $canceledRides,
                    'today_rides' => $todayRides,
                    'total_spent' => round($totalSpent, 2),
                    'this_month_spent' => round($thisMonthSpent, 2),
                ],
                'active_ride' => $activeRide,
                'can_cancel' => $canCancel,
                'active_ride_status' => $activeRide?->status,
                'active_ride_status_label' => $activeRide ? $this->getStatusLabel($activeRide->status) : null,
                'recent_rides' => $recentRides,
            ],
        ]);
    }

    /**
     * Get available drivers near pickup location
     * Allows passengers to see and choose drivers before creating a ride request
     */
    public function availableDrivers(Request $request)
    {
        $request->validate([
            'pickup_place_id' => ['nullable', 'integer', 'exists:places,id'],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:50'], // Default 5km, max 50km
        ]);

        // Get pickup coordinates
        $pickupLat = null;
        $pickupLng = null;
        $pickupPlace = null;

        if ($request->filled('pickup_place_id')) {
            $pickupPlace = Place::findOrFail($request->pickup_place_id);
            $pickupLat = $pickupPlace->latitude;
            $pickupLng = $pickupPlace->longitude;
        } elseif ($request->filled('pickup_lat') && $request->filled('pickup_lng')) {
            $pickupLat = $request->pickup_lat;
            $pickupLng = $request->pickup_lng;
        } else {
            return response()->json([
                'message' => 'Either pickup_place_id or both pickup_lat and pickup_lng must be provided.',
                'errors' => [
                    'pickup_place_id' => ['Either pickup_place_id or both pickup_lat and pickup_lng must be provided.'],
                ],
            ], 422);
        }

        $radiusKm = $request->input('radius', 5); // Default 5km radius

        // Get all approved, online drivers with location
        $drivers = DriverProfile::where('status', DriverProfile::STATUS_APPROVED)
            ->where('is_online', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->with(['user:id,name,email,phone'])
            ->get()
            ->map(function ($driverProfile) use ($pickupLat, $pickupLng, $radiusKm) {
                // Calculate distance from pickup to driver
                $distance = $this->haversineDistance(
                    $pickupLat,
                    $pickupLng,
                    $driverProfile->current_lat,
                    $driverProfile->current_lng
                );

                // Check if driver has an active ride
                $hasActiveRide = Ride::where('driver_id', $driverProfile->user_id)
                    ->whereIn('status', [
                        Ride::STATUS_ASSIGNED,
                        Ride::STATUS_ACCEPTED,
                        Ride::STATUS_IN_PROGRESS,
                    ])
                    ->exists();

                // Parse vehicle make and model
                $vehicleMake = null;
                $vehicleModel = null;
                if ($driverProfile->vehicle_type) {
                    $parts = explode(' ', $driverProfile->vehicle_type, 2);
                    $vehicleMake = $parts[0] ?? null;
                    $vehicleModel = $parts[1] ?? null;
                }

                // Get driver's current place
                $currentPlace = Place::where('latitude', $driverProfile->current_lat)
                    ->where('longitude', $driverProfile->current_lng)
                    ->first();

                // Get average rating
                $averageRating = Feedback::where('to_user_id', $driverProfile->user_id)
                    ->where('from_role', 'passenger')
                    ->avg('rating');
                
                $totalRatings = Feedback::where('to_user_id', $driverProfile->user_id)
                    ->where('from_role', 'passenger')
                    ->count();

                return [
                    'id' => $driverProfile->user_id, // Use user_id as identifier
                    'driver_profile_id' => $driverProfile->id,
                    'name' => $driverProfile->user->name,
                    'email' => $driverProfile->user->email,
                    'phone' => $driverProfile->user->phone,
                    'vehicle_type' => $driverProfile->vehicle_type,
                    'vehicle_make' => $vehicleMake,
                    'vehicle_model' => $vehicleModel,
                    'plate_number' => $driverProfile->plate_number,
                    'license_number' => $driverProfile->license_number,
                    'distance_km' => round($distance, 2),
                    'lat' => $driverProfile->current_lat,
                    'lng' => $driverProfile->current_lng,
                    'current_place' => $currentPlace ? [
                        'id' => $currentPlace->id,
                        'name' => $currentPlace->name,
                        'address' => $currentPlace->address ?? $currentPlace->name . ', Bulan, Sorsogon',
                    ] : null,
                    'average_rating' => round($averageRating ?? 0, 2),
                    'total_ratings' => $totalRatings,
                    'is_available' => !$hasActiveRide, // True if no active ride
                    'location_updated_at' => $driverProfile->location_updated_at,
                ];
            })
            ->filter(function ($driver) use ($radiusKm) {
                // Filter by radius and availability
                return $driver['distance_km'] <= $radiusKm && $driver['is_available'];
            })
            ->sortBy('distance_km') // Sort by distance (closest first)
            ->values()
            ->take(20); // Limit to 20 closest available drivers

        return response()->json([
            'data' => $drivers,
            'meta' => [
                'pickup_location' => [
                    'lat' => $pickupLat,
                    'lng' => $pickupLng,
                    'place' => $pickupPlace ? [
                        'id' => $pickupPlace->id,
                        'name' => $pickupPlace->name,
                        'address' => $pickupPlace->address ?? $pickupPlace->name . ', Bulan, Sorsogon',
                    ] : null,
                ],
                'radius_km' => $radiusKm,
                'total_available' => $drivers->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Check if passenger has active ride
        $activeRide = Ride::where('passenger_id', $user->id)
            ->whereIn('status', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_IN_PROGRESS,
            ])
            ->first();

        if ($activeRide) {
            return response()->json([
                'message' => 'You already have an active ride. Please complete or cancel it first.',
                'active_ride' => $activeRide->load(['pickupPlace', 'dropoffPlace']),
            ], 409);
        }

        $validated = $request->validate([
            // New: Use places (preferred)
            'pickup_place_id'  => ['required_without_all:pickup_lat', 'nullable', 'exists:places,id'],
            'dropoff_place_id' => ['required_without_all:drop_lat', 'nullable', 'exists:places,id'],
            // Legacy: Direct coordinates (for backward compatibility)
            'pickup_lat'       => ['required_without_all:pickup_place_id', 'nullable', 'numeric', 'between:-90,90'],
            'pickup_lng'       => ['required_with:pickup_lat', 'nullable', 'numeric', 'between:-180,180'],
            'pickup_address'  => ['nullable', 'string', 'max:255'],
            'drop_lat'         => ['required_without_all:dropoff_place_id', 'nullable', 'numeric', 'between:-90,90'],
            'drop_lng'         => ['required_with:drop_lat', 'nullable', 'numeric', 'between:-180,180'],
            'drop_address'    => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'preferred_driver_id' => ['nullable', 'integer', 'exists:users,id'], // Optional: preferred driver
        ]);

        // Get places if place_ids provided
        $pickupPlace = null;
        $dropoffPlace = null;
        $pickupLat = null;
        $pickupLng = null;
        $dropLat = null;
        $dropLng = null;

        if ($validated['pickup_place_id'] ?? null) {
            $pickupPlace = Place::findOrFail($validated['pickup_place_id']);
            $pickupLat = $pickupPlace->latitude;
            $pickupLng = $pickupPlace->longitude;
        } else {
            $pickupLat = $validated['pickup_lat'];
            $pickupLng = $validated['pickup_lng'];
        }

        if ($validated['dropoff_place_id'] ?? null) {
            $dropoffPlace = Place::findOrFail($validated['dropoff_place_id']);
            $dropLat = $dropoffPlace->latitude;
            $dropLng = $dropoffPlace->longitude;
        } else {
            $dropLat = $validated['drop_lat'];
            $dropLng = $validated['drop_lng'];
        }

        $fare = $this->calculateFare($pickupLat, $pickupLng, $dropLat, $dropLng);

        // If preferred driver is specified, validate they are available
        $preferredDriverId = $validated['preferred_driver_id'] ?? null;
        $preferredDriver = null;
        $driverAssigned = false;

        if ($preferredDriverId) {
            $preferredDriver = DriverProfile::where('user_id', $preferredDriverId)
                ->where('status', DriverProfile::STATUS_APPROVED)
                ->where('is_online', true)
                ->first();

            if (!$preferredDriver) {
                return response()->json([
                    'message' => 'Preferred driver is not available or not online.',
                    'errors' => [
                        'preferred_driver_id' => ['The selected driver is not available or not online.'],
                    ],
                ], 422);
            }

            // Check if preferred driver has an active ride
            $hasActiveRide = Ride::where('driver_id', $preferredDriverId)
                ->whereIn('status', [
                    Ride::STATUS_ASSIGNED,
                    Ride::STATUS_IN_PROGRESS,
                ])
                ->exists();

            if ($hasActiveRide) {
                return response()->json([
                    'message' => 'Preferred driver is currently on another ride.',
                    'errors' => [
                        'preferred_driver_id' => ['The selected driver is currently on another ride.'],
                    ],
                ], 422);
            }
        }

        // Create ride request
        $ride = Ride::create([
            'passenger_id'     => $user->id,
            'driver_id'        => $preferredDriverId, // Will be null if no preferred driver
            'pickup_place_id' => $pickupPlace?->id,
            'dropoff_place_id' => $dropoffPlace?->id,
            'pickup_lat'      => $pickupLat,
            'pickup_lng'      => $pickupLng,
            'pickup_address'  => $pickupPlace?->address ?? $validated['pickup_address'] ?? null,
            'drop_lat'        => $dropLat,
            'drop_lng'        => $dropLng,
            'drop_address'    => $dropoffPlace?->address ?? $validated['drop_address'] ?? null,
            'fare'            => $fare,
            'notes'           => $validated['notes'] ?? null,
            'status'          => $preferredDriverId ? Ride::STATUS_ASSIGNED : Ride::STATUS_REQUESTED,
            'requested_at'    => now(),
            'accepted_at'     => null, // Will be set when driver actually accepts
        ]);

        // NO NOTIFICATION when passenger selects preferred driver
        // Notification will be sent only when driver accepts the ride
        if ($preferredDriverId) {
            $message = 'Ride requested and assigned to your preferred driver. Waiting for driver acceptance.';
        } else {
            $message = 'Ride requested. Waiting for nearby driver.';
        }

        return response()->json([
            'message' => $message,
            'data'    => $ride->load(['pickupPlace', 'dropoffPlace', 'driver:id,name,email']),
        ], 201);
    }

    public function current(Request $request)
    {
        $ride = Ride::where('passenger_id', $request->user()->id)
            ->whereIn('status', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_IN_PROGRESS,
            ])
            ->latest()
            ->with([
                'driver:id,name,email,phone',
                'driver.driverProfile:user_id,vehicle_type,plate_number',
                'pickupPlace',
                'dropoffPlace',
            ])
            ->first();

        if (! $ride) {
            return response()->json([
                'data' => null,
                'can_cancel' => false,
                'status' => null,
            ], 200);
        }

        // Format driver info if assigned
        if ($ride->driver) {
            $ride->driver->vehicle_type = $ride->driver->driverProfile?->vehicle_type;
        }

        // Determine if cancellation is available
        $canCancel = in_array($ride->status, [
            Ride::STATUS_REQUESTED,
            Ride::STATUS_ASSIGNED,
            Ride::STATUS_ACCEPTED,
        ], true);

        return response()->json([
            'data' => $ride,
            'can_cancel' => $canCancel,
            'status' => $ride->status,
            'status_label' => $this->getStatusLabel($ride->status),
        ]);
    }

    public function history(Request $request)
    {
        $rides = Ride::where('passenger_id', $request->user()->id)
            ->whereIn('status', [Ride::STATUS_COMPLETED, Ride::STATUS_CANCELED])
            ->latest()
            ->with(['pickupPlace', 'dropoffPlace', 'driver:id,name,email'])
            ->paginate(10);

        return response()->json($rides);
    }

    public function cancel(Request $request, Ride $ride)
    {
        $user = $request->user();

        // Validate ownership
        if ($ride->passenger_id !== $user->id) {
            return response()->json([
                'message' => 'You can only cancel your own rides.',
                'error' => 'unauthorized',
            ], 403);
        }

        // Validate request body (optional reason)
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Check if cancellation is allowed
        $allowedStatuses = [
            Ride::STATUS_REQUESTED,
            Ride::STATUS_ASSIGNED,
            Ride::STATUS_ACCEPTED,
        ];

        if (! in_array($ride->status, $allowedStatuses, true)) {
            $statusLabel = $this->getStatusLabel($ride->status);
            return response()->json([
                'message' => 'Ride can no longer be canceled.',
                'current_status' => $ride->status,
                'status_label' => $statusLabel,
                'can_cancel' => false,
                'reason' => match($ride->status) {
                    Ride::STATUS_IN_PROGRESS => 'Ride is already in progress. Driver has picked up the passenger.',
                    Ride::STATUS_COMPLETED => 'Ride has already been completed.',
                    Ride::STATUS_CANCELED => 'Ride has already been canceled.',
                    default => 'Ride cannot be canceled at this stage.',
                },
            ], 422);
        }

        // Update ride status to canceled
        $ride->update([
            'status'              => Ride::STATUS_CANCELED,
            'canceled_at'         => now(),
            'cancellation_reason' => $validated['reason'] ?? null,
        ]);

        $ride->refresh();
        
        // Notify passenger that ride was cancelled
        NotificationService::notifyRideCancelled($ride);

        // If driver was assigned, notify them about cancellation
        if ($ride->driver_id) {
            // Optionally notify driver about passenger cancellation
            // This could be added to NotificationService if needed
        }

        return response()->json([
            'message' => 'Ride cancelled successfully',
            'data' => [
                'ride' => $ride->fresh(['pickupPlace', 'dropoffPlace', 'driver:id,name,email,phone']),
                'status' => $ride->status,
                'status_label' => $this->getStatusLabel($ride->status),
                'canceled_at' => $ride->canceled_at,
            ],
        ], 200);
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            Ride::STATUS_REQUESTED => 'Requested',
            Ride::STATUS_ASSIGNED => 'Assigned',
            Ride::STATUS_ACCEPTED => 'Accepted',
            Ride::STATUS_IN_PROGRESS => 'In Progress',
            Ride::STATUS_COMPLETED => 'Completed',
            Ride::STATUS_CANCELED => 'Canceled',
            default => 'Unknown',
        };
    }

    private function calculateFare(float $pickupLat, float $pickupLng, float $dropLat, float $dropLng): float
    {
        $baseFare = (float) config('services.fare.base', 20);
        $perKm = (float) config('services.fare.per_km', 5);
        $distanceKm = $this->haversineDistance($pickupLat, $pickupLng, $dropLat, $dropLng);

        return round($baseFare + ($perKm * $distanceKm), 2);
    }

    private function haversineDistance(float $latFrom, float $lonFrom, float $latTo, float $lonTo): float
    {
        $earthRadius = 6371; // km
        $latFromRad = deg2rad($latFrom);
        $lonFromRad = deg2rad($lonFrom);
        $latToRad = deg2rad($latTo);
        $lonToRad = deg2rad($lonTo);

        $latDelta = $latToRad - $latFromRad;
        $lonDelta = $lonToRad - $lonFromRad;

        $a = sin($latDelta / 2) ** 2 + cos($latFromRad) * cos($latToRad) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

