<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Place;
use App\Models\Ride;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $profile = DriverProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json([
                'message' => 'Driver profile not found',
            ], 404);
        }

        $today = today();
        $thisMonth = now()->startOfMonth();

        // Stats
        $totalRides = Ride::where('driver_id', $user->id)->count();
        $completedRides = Ride::where('driver_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->count();
        
        $todayRides = Ride::where('driver_id', $user->id)
            ->whereDate('created_at', $today)
            ->count();
        
        $todayEarnings = Ride::where('driver_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->whereDate('completed_at', $today)
            ->sum('fare');
        
        $thisMonthEarnings = Ride::where('driver_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$thisMonth, now()])
            ->sum('fare');
        
        $totalEarnings = Ride::where('driver_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->sum('fare');

        $activeRide = Ride::where('driver_id', $user->id)
            ->whereIn('status', [Ride::STATUS_ASSIGNED, Ride::STATUS_ACCEPTED, Ride::STATUS_IN_PROGRESS])
            ->with(['passenger:id,name,email,phone', 'pickupPlace', 'dropoffPlace'])
            ->first();

        $recentRides = Ride::where('driver_id', $user->id)
            ->with(['passenger:id,name,email,phone', 'pickupPlace', 'dropoffPlace'])
            ->latest()
            ->limit(10)
            ->get(['id', 'passenger_id', 'status', 'fare', 'completed_at', 'created_at', 'accepted_at', 'picked_up_at']);

        // Get recent completed rides (last 5 completed bookings)
        $recentCompletedRides = Ride::where('driver_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->with(['passenger:id,name,email,phone', 'pickupPlace', 'dropoffPlace'])
            ->latest('completed_at')
            ->limit(5)
            ->get(['id', 'passenger_id', 'status', 'fare', 'completed_at', 'created_at', 'accepted_at', 'picked_up_at']);

        // Parse vehicle info
        $vehicleMake = null;
        $vehicleModel = null;
        if ($profile->vehicle_type) {
            $parts = explode(' ', $profile->vehicle_type, 2);
            $vehicleMake = $parts[0] ?? null;
            $vehicleModel = $parts[1] ?? null;
        }

        return response()->json([
            'data' => [
                'profile' => [
                    'id' => $profile->id,
                    'status' => $profile->status,
                    'is_online' => $profile->is_online ?? false,
                    'vehicle_make' => $vehicleMake,
                    'vehicle_model' => $vehicleModel,
                    'plate_number' => $profile->plate_number,
                    'current_lat' => $profile->current_lat,
                    'current_lng' => $profile->current_lng,
                ],
                'stats' => [
                    'total_rides' => $totalRides,
                    'completed_rides' => $completedRides,
                    'today_rides' => $todayRides,
                    'today_earnings' => round($todayEarnings, 2),
                    'this_month_earnings' => round($thisMonthEarnings, 2),
                    'total_earnings' => round($totalEarnings, 2),
                ],
                'active_ride' => $activeRide,
                'recent_rides' => $recentRides,
                'recent_completed_rides' => $recentCompletedRides,
            ],
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Query driverProfile directly from database to ensure we get latest status
        // This avoids any relationship caching issues
        $profile = DriverProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json([
                'message' => 'Driver profile not found',
            ], 404);
        }

        // Parse vehicle_type to extract make and model if possible
        // Format is usually "Make Model" or just "Make"
        $vehicleMake = null;
        $vehicleModel = null;
        if ($profile->vehicle_type) {
            $parts = explode(' ', $profile->vehicle_type, 2);
            $vehicleMake = $parts[0] ?? null;
            $vehicleModel = $parts[1] ?? null;
        }

        // Find current place based on coordinates
        $currentPlace = null;
        if ($profile->current_lat && $profile->current_lng) {
            $currentPlace = Place::where('latitude', $profile->current_lat)
                ->where('longitude', $profile->current_lng)
                ->first();
        }

        $data = [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'status' => $profile->status,
            'is_online' => $profile->is_online ?? false,
            'vehicle_make' => $vehicleMake,
            'vehicle_model' => $vehicleModel,
            'plate_number' => $profile->plate_number,
            'license_number' => $profile->license_number,
            'vehicle_type' => $profile->vehicle_type,
            'lat' => $profile->current_lat,
            'lng' => $profile->current_lng,
            'location_updated_at' => $profile->location_updated_at,
            'current_place' => $currentPlace ? [
                'id' => $currentPlace->id,
                'name' => $currentPlace->name,
                'address' => $currentPlace->address ?? $currentPlace->name . ', Bulan, Sorsogon',
                'category' => $currentPlace->category,
            ] : null,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ];

        return response()->json(['data' => $data]);
    }

    public function updateAvailability(Request $request)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);

        $validated = $request->validate([
            'is_online' => ['required', 'boolean'],
        ]);

        $driverProfile->update([
            'is_online' => $validated['is_online'],
        ]);

        // If going online, ensure location is set
        if ($validated['is_online'] && (! $driverProfile->current_lat || ! $driverProfile->current_lng)) {
            return response()->json([
                'message' => 'Availability updated, but please update your location to receive ride requests.',
                'is_online' => true,
                'warning' => 'Location not set',
            ]);
        }

        return response()->json([
            'message' => 'Availability updated.',
            'is_online' => $validated['is_online'],
        ]);
    }

    public function updateLocation(Request $request)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);

        // Accept either place_id (new method - preferred) or lat/lng (backward compatible)
        $request->validate([
            'place_id' => ['nullable', 'integer', 'exists:places,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'place_id.exists' => 'The selected place does not exist.',
            'place_id.integer' => 'The place id must be a valid integer.',
            'lat.numeric' => 'The latitude must be a number.',
            'lat.between' => 'The latitude must be between -90 and 90.',
            'lng.numeric' => 'The longitude must be a number.',
            'lng.between' => 'The longitude must be between -180 and 180.',
        ]);

        // Check if we have place_id OR both lat/lng
        $hasPlaceId = $request->has('place_id') && $request->place_id !== null;
        $hasLat = $request->has('lat') && $request->lat !== null;
        $hasLng = $request->has('lng') && $request->lng !== null;

        if (!$hasPlaceId && (!$hasLat || !$hasLng)) {
            return response()->json([
                'message' => 'Either place_id or both lat and lng must be provided.',
                'errors' => [
                    'place_id' => ['Either place_id or both lat and lng must be provided.'],
                ],
            ], 422);
        }

        $latitude = null;
        $longitude = null;
        $place = null;

        // If place_id is provided, fetch coordinates from place (preferred method)
        if ($hasPlaceId) {
            $place = Place::findOrFail($request->place_id);
            $latitude = $place->latitude;
            $longitude = $place->longitude;
        } else {
            // Fallback to direct lat/lng (backward compatible)
            $latitude = $request->lat;
            $longitude = $request->lng;
        }

        $driverProfile->update([
            'current_lat' => $latitude,
            'current_lng' => $longitude,
            'location_updated_at' => now(),
        ]);

        $responseData = [
            'message' => 'Location updated.',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        // If place was found, include place details in response
        if ($place) {
            $responseData['place'] = [
                'id' => $place->id,
                'name' => $place->name,
                'address' => $place->address ?? $place->name . ', Bulan, Sorsogon',
            ];
        }

        return response()->json($responseData);
    }

    public function queue(Request $request)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);
        $user = $request->user();

        if (! $driverProfile->is_online) {
            return response()->json(['message' => 'Go online to view ride requests.'], 409);
        }

        if (! $driverProfile->current_lat || ! $driverProfile->current_lng) {
            return response()->json(['message' => 'Please update your location first.'], 400);
        }

        $radiusKm = $request->input('radius', 5); // Default 5km radius

        // Get rides that should appear in driver's queue:
        // 1. Unassigned rides (status = requested, driver_id = null) - general queue
        // 2. Assigned rides (status = assigned, driver_id = this driver) - passenger selected this driver
        // 3. Active rides (status = accepted/in_progress, driver_id = this driver) - driver already accepted
        $rides = Ride::where(function ($query) use ($user) {
            // Unassigned rides (general queue)
            $query->where(function ($q) {
                $q->whereNull('driver_id')
                  ->where('status', Ride::STATUS_REQUESTED);
            })
            // OR rides assigned to this driver
            ->orWhere(function ($q) use ($user) {
                $q->where('driver_id', $user->id)
                  ->whereIn('status', [
                      Ride::STATUS_ASSIGNED,
                      Ride::STATUS_ACCEPTED,
                      Ride::STATUS_IN_PROGRESS,
                  ]);
            });
        })
        ->whereNotIn('status', [Ride::STATUS_COMPLETED, Ride::STATUS_CANCELED])
        ->with(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone'])
        ->get()
        ->map(function ($ride) use ($driverProfile, $radiusKm, $user) {
            // Calculate distance from driver to pickup location
            $pickupLat = $ride->pickup_place_id ? $ride->pickupPlace->latitude : $ride->pickup_lat;
            $pickupLng = $ride->pickup_place_id ? $ride->pickupPlace->longitude : $ride->pickup_lng;

            $distance = $this->haversineDistance(
                $driverProfile->current_lat,
                $driverProfile->current_lng,
                $pickupLat,
                $pickupLng
            );

            // Add distance to ride object
            $ride->distance_km = round($distance, 2);

            return $ride;
        })
        ->filter(function ($ride) use ($radiusKm, $user) {
            // For assigned/accepted/in_progress rides (assigned to this driver), show regardless of distance
            if ($ride->driver_id === $user->id && in_array($ride->status, [
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_IN_PROGRESS,
            ])) {
                return true; // Always show rides assigned to this driver
            }

            // For unassigned rides, filter by radius
            return $ride->distance_km <= $radiusKm;
        })
        ->sortBy(function ($ride) use ($user) {
            // Sort priority:
            // 1. Assigned/accepted/in_progress rides for this driver (show first) - use 0 for priority
            // 2. Then by distance (closest first) - use distance_km
            $isAssigned = $ride->driver_id === $user->id && in_array($ride->status, [
                Ride::STATUS_ASSIGNED,
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_IN_PROGRESS,
            ]);
            
            // Return a sortable value: assigned rides get priority 0, others get distance
            return $isAssigned ? 0 : $ride->distance_km;
        })
        ->values()
        ->take(20); // Increased limit to show more rides

        return response()->json(['data' => $rides]);
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

    public function accept(Request $request, Ride $ride)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);

        if (! $driverProfile->is_online) {
            return response()->json(['message' => 'Go online before accepting rides.'], 409);
        }

        // Check if driver already has an active ride
        // CRITICAL: Only count ACCEPTED and IN_PROGRESS as active
        // ASSIGNED rides should NOT block new bookings (driver can accept those)
        $activeRide = Ride::where('driver_id', $driverProfile->user_id)
            ->whereIn('status', [
                Ride::STATUS_ACCEPTED,      // Driver accepted, ride in progress
                Ride::STATUS_IN_PROGRESS,   // Ride actively happening (passenger picked up)
            ])
            ->whereNotIn('status', [
                Ride::STATUS_COMPLETED,
                Ride::STATUS_CANCELED,
                Ride::STATUS_ASSIGNED,      // Exclude assigned - driver can accept these
            ])
            ->first();

        if ($activeRide) {
            $activeRide->load(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone']);
            
            // Build detailed message based on ride status
            $statusMessage = match($activeRide->status) {
                Ride::STATUS_ACCEPTED => 'You have an accepted ride. Please complete it before accepting a new one.',
                Ride::STATUS_IN_PROGRESS => 'You have a ride in progress. Please complete it first.',
                default => 'You have an active ride. Please complete it first.',
            };
            
            return response()->json([
                'message' => 'You already have an active ride. Please complete it before accepting a new one.',
                'details' => $statusMessage,
                'error' => 'active_ride_exists',
                'active_ride' => [
                    'id' => $activeRide->id,
                    'status' => $activeRide->status,
                    'status_label' => $this->getStatusLabel($activeRide->status),
                    'passenger' => [
                        'id' => $activeRide->passenger->id,
                        'name' => $activeRide->passenger->name,
                        'email' => $activeRide->passenger->email,
                        'phone' => $activeRide->passenger->phone,
                    ],
                    'pickup_place' => $activeRide->pickupPlace ? [
                        'id' => $activeRide->pickupPlace->id,
                        'name' => $activeRide->pickupPlace->name,
                        'address' => $activeRide->pickupPlace->address ?? $activeRide->pickupPlace->name . ', Bulan, Sorsogon',
                    ] : null,
                    'dropoff_place' => $activeRide->dropoffPlace ? [
                        'id' => $activeRide->dropoffPlace->id,
                        'name' => $activeRide->dropoffPlace->name,
                        'address' => $activeRide->dropoffPlace->address ?? $activeRide->dropoffPlace->name . ', Bulan, Sorsogon',
                    ] : null,
                    'fare' => $activeRide->fare,
                    'requested_at' => $activeRide->requested_at,
                    'accepted_at' => $activeRide->accepted_at,
                    'picked_up_at' => $activeRide->picked_up_at,
                ],
            ], 409);
        }

        // Use database transaction to prevent race condition
        return DB::transaction(function () use ($ride, $driverProfile) {
            // Re-check status (another driver might have accepted)
            $ride->refresh();
            
            // Driver can accept rides that are REQUESTED (no driver assigned) 
            // or ASSIGNED (passenger selected this driver as preferred)
            if (! in_array($ride->status, [Ride::STATUS_REQUESTED, Ride::STATUS_ASSIGNED], true)) {
                return response()->json(['message' => 'Ride is no longer available.'], 409);
            }

            // If ride is REQUESTED, it shouldn't have a driver_id yet
            // If ride is ASSIGNED, it should have this driver's ID (passenger selected them)
            if ($ride->status === Ride::STATUS_REQUESTED && $ride->driver_id) {
                return response()->json(['message' => 'Ride has already been assigned to another driver.'], 409);
            }

            if ($ride->status === Ride::STATUS_ASSIGNED && $ride->driver_id !== $driverProfile->user_id) {
                return response()->json(['message' => 'This ride has been assigned to another driver.'], 409);
            }

            // Update ride to ACCEPTED status (driver has confirmed acceptance)
            $ride->update([
                'driver_id'   => $driverProfile->user_id,
                'status'      => Ride::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            $ride->refresh();
            
            // Notify passenger that driver accepted
            NotificationService::notifyDriverAccepted($ride);

            return response()->json([
                'message' => 'Ride accepted.',
                'data' => $ride->fresh(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone']),
            ]);
        });
    }

    public function pickup(Request $request, Ride $ride)
    {
        $this->assertRideOwnership($request, $ride);

        if ($ride->status !== Ride::STATUS_ACCEPTED) {
            return response()->json(['message' => 'Ride must be accepted before pickup.'], 422);
        }

        $ride->update([
            'status'       => Ride::STATUS_IN_PROGRESS,
            'picked_up_at' => now(),
        ]);

        $ride->refresh();
        
        // Notify passenger that driver is on the way (picked up)
        NotificationService::notifyDriverOnWay($ride);

        return response()->json([
            'message' => 'Passenger picked up',
            'ride' => $ride->fresh(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone']),
        ]);
    }

    public function complete(Request $request, Ride $ride)
    {
        $user = $request->user();

        // Validate ownership
        if ($ride->driver_id !== $user->id) {
            return response()->json([
                'message' => 'You can only complete your own rides.',
                'error' => 'unauthorized',
            ], 403);
        }

        // Check if completion is allowed - allow completion from accepted, in_progress
        // Note: Frontend may show "picked_up" but backend uses "in_progress" for this status
        $allowedStatuses = [
            Ride::STATUS_ACCEPTED,     // Driver can complete even before pickup
            Ride::STATUS_IN_PROGRESS,  // Driver picked up passenger, ride in progress
        ];

        if (! in_array($ride->status, $allowedStatuses, true)) {
            $statusLabel = $this->getStatusLabel($ride->status);
            return response()->json([
                'message' => 'Ride cannot be completed in its current status.',
                'current_status' => $ride->status,
                'status_label' => $statusLabel,
                'reason' => match($ride->status) {
                    Ride::STATUS_REQUESTED => 'Ride must be accepted by driver before completion.',
                    Ride::STATUS_ASSIGNED => 'Ride must be accepted by driver before completion.',
                    Ride::STATUS_COMPLETED => 'Ride is already completed.',
                    Ride::STATUS_CANCELED => 'Ride has been canceled.',
                    default => 'Ride must be accepted or in progress to be completed.',
                },
            ], 422);
        }

        // Update ride status to completed
        $ride->update([
            'status'       => Ride::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $ride->refresh();
        
        // Notify passenger that trip is completed
        NotificationService::notifyTripCompleted($ride);

        return response()->json([
            'message' => 'Ride completed successfully',
            'data' => [
                'ride' => $ride->fresh(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone']),
                'status' => $ride->status,
                'status_label' => $this->getStatusLabel($ride->status),
                'completed_at' => $ride->completed_at,
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

    /**
     * Decline a ride request
     */
    public function decline(Request $request, Ride $ride)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);
        $user = $request->user();

        // Driver can decline REQUESTED rides (general queue) or ASSIGNED rides (passenger selected them)
        if (! in_array($ride->status, [Ride::STATUS_REQUESTED, Ride::STATUS_ASSIGNED], true)) {
            return response()->json(['message' => 'Ride is no longer available to decline.'], 409);
        }

        // If ride is ASSIGNED, ensure it's assigned to this driver
        if ($ride->status === Ride::STATUS_ASSIGNED && $ride->driver_id !== $driverProfile->user_id) {
            return response()->json(['message' => 'This ride is not assigned to you.'], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $ride->update([
            'driver_declined_at' => now(),
            'declined_by_driver_id' => $user->id,
            'decline_reason' => $validated['reason'] ?? null,
        ]);

        // Notify passenger that driver cancelled
        NotificationService::notifyDriverCancelled($ride, $validated['reason'] ?? 'Driver declined the ride');

        return response()->json([
            'message' => 'Ride declined.',
        ]);
    }

    /**
     * Get passenger profile (for drivers to view)
     */
    public function viewPassengerProfile(Request $request, User $passenger)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);

        // Verify passenger is part of driver's rides
        $hasRideWithPassenger = Ride::where('driver_id', $driverProfile->user_id)
            ->where('passenger_id', $passenger->id)
            ->exists();

        if (!$hasRideWithPassenger) {
            return response()->json(['message' => 'You can only view profiles of passengers you have rides with.'], 403);
        }

        // Get passenger stats
        $totalRides = Ride::where('passenger_id', $passenger->id)->count();
        $completedRides = Ride::where('passenger_id', $passenger->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->count();
        
        // Get average rating
        $averageRating = \App\Models\Feedback::where('to_user_id', $passenger->id)
            ->where('from_role', 'driver')
            ->avg('rating');

        return response()->json([
            'data' => [
                'id' => $passenger->id,
                'name' => $passenger->name,
                'email' => $passenger->email,
                'phone' => $passenger->phone,
                'created_at' => $passenger->created_at,
                'stats' => [
                    'total_rides' => $totalRides,
                    'completed_rides' => $completedRides,
                    'average_rating' => round($averageRating ?? 0, 2),
                ],
            ],
        ]);
    }

    /**
     * Get driver's ride history
     */
    public function history(Request $request)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);
        $user = $request->user();

        $rides = Ride::where('driver_id', $user->id)
            ->with(['passenger:id,name,email,phone', 'pickupPlace', 'dropoffPlace'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($rides);
    }

    protected function getApprovedDriverProfile(Request $request): DriverProfile
    {
        $user = $request->user();
        
        // Query driverProfile directly from database to ensure we get latest status
        // This avoids any relationship caching issues
        $profile = DriverProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            abort(403, 'Driver profile not found. Please complete your driver registration.');
        }

        if ($profile->status === DriverProfile::STATUS_PENDING) {
            abort(403, 'Driver profile is pending admin approval. Please wait for approval.');
        }

        if ($profile->status === DriverProfile::STATUS_REJECTED) {
            abort(403, 'Driver profile has been rejected. Please contact admin.');
        }

        if ($profile->status !== DriverProfile::STATUS_APPROVED) {
            abort(403, 'Driver profile is not approved. Current status: ' . $profile->status);
        }

        return $profile;
    }

    protected function assertRideOwnership(Request $request, Ride $ride): void
    {
        if ($ride->driver_id !== $request->user()->id) {
            abort(403, 'You are not assigned to this ride.');
        }
    }
}

