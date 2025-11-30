<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emergency;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    /**
     * Report an emergency (Driver or Passenger)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $role = $user->getRoleNames()->first();
        
        if (!in_array($role, ['driver', 'passenger'])) {
            return response()->json(['message' => 'Only drivers and passengers can report emergencies'], 403);
        }

        $validated = $request->validate([
            'ride_id' => ['nullable', 'exists:rides,id'],
            'type' => ['required', 'in:safety_concern,driver_emergency,passenger_emergency,accident,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        // Verify ride_id belongs to user if provided
        if ($validated['ride_id']) {
            $ride = \App\Models\Ride::find($validated['ride_id']);
            if ($ride && $ride->passenger_id !== $user->id && $ride->driver_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized access to this ride'], 403);
            }
        }

        $emergency = Emergency::create([
            'user_id' => $user->id,
            'ride_id' => $validated['ride_id'] ?? null,
            'reported_by_role' => $role,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => Emergency::STATUS_PENDING,
        ]);

        // Notify all admins
        NotificationService::notifyEmergencyToAdmins(
            $emergency->id,
            "Emergency Alert: {$validated['title']}",
            "Emergency reported by {$user->name} ({$role}). {$validated['description']}",
            [
                'type' => $validated['type'],
                'reported_by' => $user->name,
                'reported_by_role' => $role,
            ]
        );

        return response()->json([
            'message' => 'Emergency reported successfully. Admin has been notified.',
            'data' => $emergency->load(['ride:id,status', 'user:id,name,email,phone']),
        ], 201);
    }

    /**
     * Get user's emergency reports
     */
    public function myReports(Request $request)
    {
        $user = $request->user();
        
        $emergencies = Emergency::where('user_id', $user->id)
            ->with(['ride:id,status,fare'])
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json($emergencies);
    }

    /**
     * Admin: Get all emergencies
     */
    public function adminIndex(Request $request)
    {
        $query = Emergency::with(['user:id,name,email,phone', 'ride:id,status', 'acknowledgedBy:id,name']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $emergencies = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json($emergencies);
    }

    /**
     * Admin: Acknowledge emergency
     */
    public function acknowledge(Request $request, Emergency $emergency)
    {
        $admin = $request->user();

        if (!$admin->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can acknowledge emergencies'], 403);
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $emergency->update([
            'status' => Emergency::STATUS_ACKNOWLEDGED,
            'acknowledged_by' => $admin->id,
            'acknowledged_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Emergency acknowledged',
            'data' => $emergency->load(['user:id,name,email,phone', 'acknowledgedBy:id,name']),
        ]);
    }

    /**
     * Admin: Resolve emergency
     */
    public function resolve(Request $request, Emergency $emergency)
    {
        $admin = $request->user();

        if (!$admin->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can resolve emergencies'], 403);
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $emergency->update([
            'status' => Emergency::STATUS_RESOLVED,
            'admin_notes' => $validated['admin_notes'] ?? $emergency->admin_notes,
        ]);

        return response()->json([
            'message' => 'Emergency resolved',
            'data' => $emergency->load(['user:id,name,email,phone', 'acknowledgedBy:id,name']),
        ]);
    }
}

