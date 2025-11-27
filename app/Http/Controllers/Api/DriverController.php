<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Ride;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json([
            'data' => $request->user()->load('driverProfile'),
        ]);
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

        return response()->json(['message' => 'Availability updated.']);
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
        ]);

        return response()->json(['message' => 'Location updated.']);
    }

    public function queue(Request $request)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);

        if (! $driverProfile->is_online) {
            return response()->json(['message' => 'Go online to view ride requests.'], 409);
        }

        $rides = Ride::where('status', Ride::STATUS_REQUESTED)
            ->latest('requested_at')
            ->limit(10)
            ->get();

        return response()->json(['data' => $rides]);
    }

    public function accept(Request $request, Ride $ride)
    {
        $driverProfile = $this->getApprovedDriverProfile($request);

        if (! $driverProfile->is_online) {
            return response()->json(['message' => 'Go online before accepting rides.'], 409);
        }

        if ($ride->status !== Ride::STATUS_REQUESTED) {
            return response()->json(['message' => 'Ride is no longer available.'], 409);
        }

        $ride->update([
            'driver_id'   => $driverProfile->user_id,
            'status'      => Ride::STATUS_ASSIGNED,
            'accepted_at' => now(),
        ]);

        return response()->json(['message' => 'Ride accepted.', 'data' => $ride->fresh()]);
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

        return response()->json(['message' => 'Passenger picked up.']);
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

        return response()->json(['message' => 'Ride completed.']);
    }

    protected function getApprovedDriverProfile(Request $request): DriverProfile
    {
        $profile = $request->user()->driverProfile;

        if (! $profile || $profile->status !== DriverProfile::STATUS_APPROVED) {
            abort(403, 'Driver profile is pending approval.');
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

