<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Illuminate\Http\Request;

class PassengerRideController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'pickup_lat'    => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng'    => ['required', 'numeric', 'between:-180,180'],
            'pickup_address'=> ['nullable', 'string', 'max:255'],
            'drop_lat'      => ['required', 'numeric', 'between:-90,90'],
            'drop_lng'      => ['required', 'numeric', 'between:-180,180'],
            'drop_address'  => ['nullable', 'string', 'max:255'],
        ]);

        $fare = $this->calculateFare(
            $validated['pickup_lat'],
            $validated['pickup_lng'],
            $validated['drop_lat'],
            $validated['drop_lng']
        );

        $ride = Ride::create([
            'passenger_id'   => $user->id,
            'pickup_lat'     => $validated['pickup_lat'],
            'pickup_lng'     => $validated['pickup_lng'],
            'pickup_address' => $validated['pickup_address'] ?? null,
            'drop_lat'       => $validated['drop_lat'],
            'drop_lng'       => $validated['drop_lng'],
            'drop_address'   => $validated['drop_address'] ?? null,
            'fare'           => $fare,
            'status'         => Ride::STATUS_REQUESTED,
            'requested_at'   => now(),
        ]);

        return response()->json([
            'message' => 'Ride requested. Waiting for nearby driver.',
            'data'    => $ride,
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
            ->with('driver:id,name,email')
            ->first();

        return response()->json(['data' => $ride]);
    }

    public function history(Request $request)
    {
        $rides = Ride::where('passenger_id', $request->user()->id)
            ->whereIn('status', [Ride::STATUS_COMPLETED, Ride::STATUS_CANCELED])
            ->latest()
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

        return response()->json(['message' => 'Ride canceled.']);
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

