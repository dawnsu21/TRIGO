<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Ride;
use Illuminate\Http\Request;

class PassengerRideController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if passenger has active ride
        $activeRide = Ride::where('passenger_id', $user->id)
            ->whereIn('status', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
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
            'pickup_place_id'  => ['required_without:pickup_lat', 'nullable', 'exists:places,id'],
            'dropoff_place_id' => ['required_without:drop_lat', 'nullable', 'exists:places,id'],
            // Legacy: Direct coordinates (for backward compatibility)
            'pickup_lat'       => ['required_without:pickup_place_id', 'nullable', 'numeric', 'between:-90,90'],
            'pickup_lng'       => ['required_without:pickup_place_id', 'nullable', 'numeric', 'between:-180,180'],
            'pickup_address'  => ['nullable', 'string', 'max:255'],
            'drop_lat'         => ['required_without:dropoff_place_id', 'nullable', 'numeric', 'between:-90,90'],
            'drop_lng'         => ['required_without:dropoff_place_id', 'nullable', 'numeric', 'between:-180,180'],
            'drop_address'    => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string', 'max:500'],
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

        $ride = Ride::create([
            'passenger_id'     => $user->id,
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
            'status'          => Ride::STATUS_REQUESTED,
            'requested_at'    => now(),
        ]);

        return response()->json([
            'message' => 'Ride requested. Waiting for nearby driver.',
            'data'    => $ride->load(['pickupPlace', 'dropoffPlace', 'driver:id,name,email']),
        ], 201);
    }

    public function current(Request $request)
    {
        $ride = Ride::where('passenger_id', $request->user()->id)
            ->whereIn('status', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ASSIGNED,
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
            return response()->json(['data' => null], 404);
        }

        // Format driver info if assigned
        if ($ride->driver) {
            $ride->driver->vehicle_type = $ride->driver->driverProfile?->vehicle_type;
        }

        return response()->json(['data' => $ride]);
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
        if ($ride->passenger_id !== $request->user()->id) {
            abort(403, 'You can only cancel your own rides.');
        }

        if (! in_array($ride->status, [Ride::STATUS_REQUESTED, Ride::STATUS_ASSIGNED], true)) {
            return response()->json(['message' => 'Ride can no longer be canceled.'], 422);
        }

        $ride->update([
            'status'              => Ride::STATUS_CANCELED,
            'canceled_at'         => now(),
            'cancellation_reason' => $request->input('reason'),
        ]);

        return response()->json([
            'message' => 'Ride cancelled successfully',
            'ride' => $ride->fresh(['pickupPlace', 'dropoffPlace']),
        ]);
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

