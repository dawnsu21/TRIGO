<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Ride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user()->load('driverProfile');
        $profile = $user->driverProfile;

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'vehicle_type' => $profile?->vehicle_type,
            'plate_number' => $profile?->plate_number,
            'license_number' => $profile?->license_number,
            'status' => $profile?->status,
            'is_online' => $profile?->is_online ?? false,
            'latitude' => $profile?->current_lat,
            'longitude' => $profile?->current_lng,
            'location_updated_at' => $profile?->location_updated_at,
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

        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $driverProfile->update([
            'current_lat' => $validated['lat'],
            'current_lng' => $validated['lng'],
            'location_updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Location updated.',
            'latitude' => $validated['lat'],
            'longitude' => $validated['lng'],
        ]);
    }

    public function queue(Request $request)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);

        if (! $driverProfile->is_online) {
            return response()->json(['message' => 'Go online to view ride requests.'], 409);
        }

        if (! $driverProfile->current_lat || ! $driverProfile->current_lng) {
            return response()->json(['message' => 'Please update your location first.'], 400);
        }

        $radiusKm = $request->input('radius', 5); // Default 5km radius

        // Get all pending rides
        $rides = Ride::where('status', Ride::STATUS_REQUESTED)
            ->with(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone'])
            ->get()
            ->map(function ($ride) use ($driverProfile, $radiusKm) {
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
            ->filter(function ($ride) use ($radiusKm) {
                // Filter by radius
                return $ride->distance_km <= $radiusKm;
            })
            ->sortBy('distance_km') // Sort by distance (closest first)
            ->values()
            ->take(10);

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

        // Use database transaction to prevent race condition
        return DB::transaction(function () use ($ride, $driverProfile) {
            // Re-check status (another driver might have accepted)
            $ride->refresh();
            
            if ($ride->status !== Ride::STATUS_REQUESTED) {
                return response()->json(['message' => 'Ride is no longer available.'], 409);
            }

            if ($ride->driver_id) {
                return response()->json(['message' => 'Ride has already been accepted by another driver.'], 409);
            }

            $ride->update([
                'driver_id'   => $driverProfile->user_id,
                'status'      => Ride::STATUS_ASSIGNED,
                'accepted_at' => now(),
            ]);

            return response()->json([
                'message' => 'Ride accepted.',
                'data' => $ride->fresh(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone']),
            ]);
        });
    }

    public function pickup(Request $request, Ride $ride)
    {
        $this->assertRideOwnership($request, $ride);

        if ($ride->status !== Ride::STATUS_ASSIGNED) {
            return response()->json(['message' => 'Ride must be accepted before pickup.'], 422);
        }

        $ride->update([
            'status'       => Ride::STATUS_IN_PROGRESS,
            'picked_up_at' => now(),
        ]);

        return response()->json([
            'message' => 'Passenger picked up',
            'ride' => $ride->fresh(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone']),
        ]);
    }

    public function complete(Request $request, Ride $ride)
    {
        $this->assertRideOwnership($request, $ride);

        if ($ride->status !== Ride::STATUS_IN_PROGRESS) {
            return response()->json(['message' => 'Ride must be in progress to complete.'], 422);
        }

        $ride->update([
            'status'       => Ride::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Ride completed',
            'ride' => $ride->fresh(['pickupPlace', 'dropoffPlace', 'passenger:id,name,email,phone']),
        ]);
    }

    protected function getApprovedDriverProfile(Request $request): DriverProfile
    {
        $profile = $request->user()->driverProfile;

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

